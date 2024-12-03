from flask import Flask, jsonify, request
from flask_mysqldb import MySQL
from dotenv import load_dotenv
import os
import logging
import time
import MySQLdb
import subprocess
import posixpath

load_dotenv()

app = Flask(__name__)

# Directory configurations
UPLOAD_DIR = "/var/deploy/uploads"
QA_DIR = "/var/deploy/qa"

# Logging configuration. Remember to uncomment when in production
logging.basicConfig(
    filename='/var/log/deploy_flask_app.log',
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)

os.makedirs(UPLOAD_DIR, exist_ok=True)
os.makedirs(QA_DIR, exist_ok=True)

# Database configuration
app.config['MYSQL_HOST'] = os.getenv('MYSQL_HOST')
app.config['MYSQL_USER'] = os.getenv('MYSQL_USER')
app.config['MYSQL_PASSWORD'] = os.getenv('MYSQL_PASSWORD')
app.config['MYSQL_DB'] = os.getenv('MYSQL_DB')

mysql = MySQL(app)

# Allowed file extensions
ALLOWED_EXTENSIONS = {'tar', 'gz', 'bz2', 'xz'}
def allowed_file(filename):
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS
def get_db_connection():
    try:
        return mysql.connection
    except MySQLdb.Error as e:
        logging.error(f"Database connection error: {e}")
        return None

# Route to get database and table information
@app.route('/database', methods=['GET'])
def database():
    connection = get_db_connection()
    if not connection:
        return jsonify({"success": False, "error": "Failed to connect to the database"}), 500

    try:
        cursor = connection.cursor()

        # Get the current database name
        cursor.execute("SELECT DATABASE();")
        current_db = cursor.fetchone()[0]

        # Get the list of tables
        cursor.execute("SHOW TABLES;")
        tables = cursor.fetchall()

        cursor.close()
        return jsonify({
            "success": True,
            "database": current_db,
            "tables": [table[0] for table in tables]
        })
    except Exception as e:
        logging.error(f"Error in /database: {e}")
        return jsonify({"success": False, "error": str(e)}), 500

# Query specific table
@app.route('/database/<table_name>', methods=['GET'])
def get_table_data(table_name):
    connection = get_db_connection()
    if not connection:
        return jsonify({"success": False, "error": "Failed to connect to the database"}), 500

    try:
        cursor = connection.cursor()

        # Query all rows from the specified table
        cursor.execute(f"SELECT * FROM {table_name}")
        rows = cursor.fetchall()
        columns = [desc[0] for desc in cursor.description]
        data = [dict(zip(columns, row)) for row in rows]

        cursor.close()
        return jsonify({
            "success": True,
            "table": table_name,
            "data": data
        })
    except Exception as e:
        logging.error(f"Error querying table {table_name}: {e}")
        return jsonify({"success": False, "error": str(e)}), 500

@app.route('/management/database', methods=['POST'])
def manage_database():
    # Get parameters from request body
    data = request.json
    database = data.get('database')
    query = data.get('query')

    # Validate required parameters
    if not database or not query:
        return jsonify({
            "success": False,
            "error": "Missing required parameters. Please provide 'database' and 'query'."
        }), 400

    connection = get_db_connection()
    if not connection:
        return jsonify({"success": False, "error": "Failed to connect to the database"}), 500

    try:
        cursor = connection.cursor()

        cursor.execute(f"USE {database};")

        cursor.execute(query)

        if query.strip().upper().startswith("SELECT"):
            rows = cursor.fetchall()
            columns = [desc[0] for desc in cursor.description]
            data = [dict(zip(columns, row)) for row in rows]
            response = {
                "success": True,
                "data": data
            }
        else:
            connection.commit()
            response = {
                "success": True,
                "message": f"Query executed successfully. {cursor.rowcount} row(s) affected."
            }

        cursor.close()
        return jsonify(response)

    except Exception as e:
        logging.error(f"Error in /management/database: {e}")
        return jsonify({
            "success": False,
            "error": str(e)
        }), 500

@app.route('/management/uploads', methods=['POST'])
def manage_uploads():
    # Get parameters from request body
    data = request.json
    action = data.get('action')
    file_name = data.get('file', None)  # Used for the 'delete' action

    # Validate action parameter
    if action not in ['clear', 'list', 'delete']:
        return jsonify({
            "success": False,
            "error": "Invalid action. Valid actions are 'clear', 'list', or 'delete'."
        }), 400

    try:
        if action == 'clear':
            # Remove all files in the upload directory
            files = os.listdir(UPLOAD_DIR)
            for file in files:
                file_path = os.path.join(UPLOAD_DIR, file)
                if os.path.isfile(file_path):
                    os.remove(file_path)
            return jsonify({
                "success": True,
                "message": "All files in the upload directory have been cleared."
            })

        elif action == 'list':
            # List all files in the upload directory
            files = os.listdir(UPLOAD_DIR)
            return jsonify({
                "success": True,
                "files": files
            })

        elif action == 'delete':
            # Delete a specific file
            if not file_name:
                return jsonify({
                    "success": False,
                    "error": "Missing parameter 'file' for the 'delete' action."
                }), 400

            file_path = os.path.join(UPLOAD_DIR, file_name)
            if os.path.isfile(file_path):
                os.remove(file_path)
                return jsonify({
                    "success": True,
                    "message": f"File '{file_name}' has been deleted successfully."
                })
            else:
                return jsonify({
                    "success": False,
                    "error": f"File '{file_name}' does not exist."
                }), 404

    except Exception as e:
        logging.error(f"Error in /management/uploads: {e}")
        return jsonify({
            "success": False,
            "error": str(e)
        }), 500


def update_bundle_status(id, bundle_name, version, status, notes=None, bundle_type=None):
    # Validate the bundle type
    valid_tables = {
        "frontend": "frontend_bundles",
        "backend": "backend_bundles",
        "dmz": "dmz_bundles"
    }

    if bundle_type not in valid_tables:
        raise ValueError(f"Invalid bundle type: {bundle_type}. Must be one of {list(valid_tables.keys())}")

    # Get the table name
    table_name = valid_tables[bundle_type]

    # Database connection
    connection = get_db_connection()
    if not connection:
        raise Exception("Database connection failed")

    try:
        cursor = connection.cursor()
        last_updated = int(time.time())

        # Insert or update record dynamically into the correct table
        sql = f"""
            INSERT INTO {table_name} (id, bundleName, versionNumber, status, lastUpdated, notes)
            VALUES (%s, %s, %s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE
                versionNumber = VALUES(versionNumber),
                status = VALUES(status),
                lastUpdated = VALUES(lastUpdated),
                notes = VALUES(notes)
        """
        params = (id, bundle_name, version, status, last_updated, notes)
        cursor.execute(sql, params)
        connection.commit()

        return {"success": True, "message": f"Bundle status updated in {table_name}"}
    except MySQLdb.Error as e:
        logging.error(f"Database error for table {table_name}: {e}")
        raise Exception(str(e))
    finally:
        cursor.close()


def update_server_history(server_name, bundle_name, version):
    connection = get_db_connection()
    if not connection:
        raise Exception("Database connection failed")

    try:
        cursor = connection.cursor()
        last_updated = int(time.time())

        cursor.execute("""
            INSERT INTO update_history (serverName, bundleName, versionNumber, lastUpdated)
            VALUES (%s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE
                bundleName = VALUES(bundleName),
                versionNumber = VALUES(versionNumber),
                lastUpdated = VALUES(lastUpdated)
        """, (server_name, bundle_name, version, last_updated))

        connection.commit()
        return {"success": True, "message": "Server history updated successfully"}
    except MySQLdb.Error as e:
        logging.error(f"Database error for update_history: {e}")
        raise Exception(str(e))
    finally:
        cursor.close()

# Route to update a bundle's status
@app.route('/update_bundle', methods=['POST'])
def update_bundle():
    try:
        data = request.json
        bundle_name = data['bundle_name']
        version = data['version']
        status = data['status']
        notes = data.get('notes', "")
        result = update_bundle_status(bundle_name, version, status, notes)
        return jsonify(result)
    except Exception as e:
        logging.error(f"Error in /update_bundle: {e}")
        return jsonify({"success": False, "error": str(e)}), 500


@app.route('/upload', methods=['POST'])
def upload_package():
    if 'file' not in request.files:
        logging.error("No file part in the request")
        return jsonify({"success": False, "error": "No file part in the request"}), 400

    file = request.files['file']
    if file.filename == '':
        logging.error("No selected file")
        return jsonify({"success": False, "error": "No selected file"}), 400

    if not allowed_file(file.filename):
        logging.error(f"File type not allowed: {file.filename}")
        return jsonify({"success": False, "error": "File type must end in .tar.gz"}), 400

    filepath = os.path.join(UPLOAD_DIR, file.filename)

    # Check if the file already exists
    if os.path.exists(filepath):
        logging.warning(f"File already exists: {filepath}")
        return jsonify({"success": False, "error": "File already exists"}), 400

    try:
        # Get required ID from the request
        id = request.form.get('id')
        if not id:
            logging.error("Package ID is required for the request")
            return jsonify({"success": False, "error": "Package ID is required for the request"}), 400
        # Extract metadata from filename
        try:
            # Remove the last two parts (e.g., `.tar.gz`)
            base_name = file.filename.rsplit('.', 2)[0]
            bundle_type, bundle_name_version = base_name.split('_', 1)
            bundle_name, version = bundle_name_version.rsplit('_', 1)
        except ValueError:
            logging.error(f"Invalid filename format: {file.filename}")
            return jsonify({"success": False, "error": "Invalid filename format. Expected 'type_bundleName_version.extension'"}), 400

        # Check for existing package information in the database
        connection = get_db_connection()
        if not connection:
            return jsonify({"success": False, "error": "Database connection failed"}), 500

        cursor = connection.cursor()
        table_name = {
            "frontend": "frontend_bundles",
            "backend": "backend_bundles",
            "dmz": "dmz_bundles"
        }.get(bundle_type)

        if not table_name:
            return jsonify({"success": False, "error": f"Invalid bundle type: {bundle_type}"}), 400

        # Query the current version details
        cursor.execute(f"SELECT id, bundleName, versionNumber, status, lastUpdated, notes FROM {table_name} WHERE id = %s", (id,))
        existing_bundle = cursor.fetchone()

        # If bundle exists, throw an error
        if existing_bundle:
            logging.warning(f"Duplicate package detected: {bundle_name} {version}")
            return jsonify({
                "success": False,
                "error": f"Duplicate package entry detected for {base_name} in {bundle_type}_bundles table. "
                f"Resolve the conflict in the database before uploading."
            }), 400

        # Save the file
        file.save(filepath)
        logging.info(f"File uploaded: {filepath}")

        # Get the optional note from the request
        notes = request.form.get('notes', '')

        # Update the database with status = "New" and the note
        try:
            update_result = update_bundle_status(
                id, bundle_name, version, "New", notes=notes, bundle_type=bundle_type)
        except Exception as e:
            logging.error(f"Database update failed: {e}")
            return jsonify({"success": False, "error": f"File uploaded but failed to update database: {str(e)}"}), 500

        return jsonify({
            "success": True,
            "message": "File uploaded and database updated successfully",
            "data": {
                "path": filepath,
                "id": id,
                "bundle_name": bundle_name,
                "version": version,
                "bundle_type": bundle_type,
                "note": notes
            }
        })

    except Exception as e:
        logging.error(f"Error saving file: {e}")
        return jsonify({"success": False, "error": str(e)}), 500


@app.route('/deploy/dev/<server_name>', methods=['POST'])
def deploy_to_dev(server_name):
    # Parse action from the request
    action = request.json.get('action', None)
    if action not in ['deploy', 'latest', 'rollback']:
        return jsonify({"success": False, "error": "Invalid action. Must be 'deploy', 'latest', or 'rollback'."}), 400

    # Extract additional parameters if provided
    package_name = request.json.get('bundle_name', None)  # For rollback
    bundle_type = request.json.get('bundle_type', 'frontend')  # Default to frontend bundles
    version_number = request.json.get('version', None)  # For rollback

    # Database table mapping
    table_name = {
        "frontend": "frontend_bundles",
        "backend": "backend_bundles",
        "dmz": "dmz_bundles"
    }.get(bundle_type)

    if not table_name:
        return jsonify({"success": False, "error": f"Invalid bundle type: {bundle_type}"}), 400

    # Target server details
    remote_user = "root"
    remote_dir = "/var/deploy/"
    install_script = "install.sh"

    # Database connection
    connection = get_db_connection()
    if not connection:
        return jsonify({"success": False, "error": "Database connection failed"}), 500

    cursor = connection.cursor()

    try:
        if action == 'latest':
            # Fetch the latest package information
            cursor.execute(
                f"SELECT serverName, bundleName, versionNumber, lastUpdated FROM update_history WHERE serverName = %s ORDER BY lastUpdated DESC LIMIT 1",
                (server_name,)
            )
            latest_package = cursor.fetchone()
            if latest_package:
                return jsonify({
                    "success": True,
                    "data": {
                        "serverName": latest_package[0],
                        "bundle_name": latest_package[1],
                        "version": str(latest_package[2]),
                        "last_updated": latest_package[3],
                    }
                })
            else:
                return jsonify({"success": False, "error": "No package history found in the database.", "server": server_name}), 404

        elif action == 'rollback':
            # Rollback to a specific package
            if not package_name or not version_number:
                return jsonify({"success": False, "error": "Bundle name and version are required for rollback."}), 400

            cursor.execute(
                f"SELECT bundleName, versionNumber FROM {table_name} WHERE bundleName = %s AND versionNumber = %s",
                (package_name, version_number)
            )
            rollback_package = cursor.fetchone()
            if rollback_package:
                local_file = f"/var/deploy/uploads/{bundle_type}_{rollback_package[0]}_{rollback_package[1]}.tar.gz"
                if not os.path.exists(local_file):
                    return jsonify({"success": False, "error": f"Package file not found: {local_file}"}), 404

                try:
                    deploy_with_scp(local_file, server_name, remote_dir, remote_user, install_script)
                    update_server_history(server_name, rollback_package[0], rollback_package[1])
                    update_bundle_status(rollback_package[0], rollback_package[1], rollback_package[2], "Pass", bundle_type=bundle_type)
                    return jsonify({
                        "success": True,
                        "message": f"Rollback to package {rollback_package[0]} version {rollback_package[1]} completed successfully."
                    })
                except Exception as e:
                    logging.error(f"Rollback failed: {e}")
                    update_bundle_status(rollback_package[0], rollback_package[1], rollback_package[2], "Fail", bundle_type=bundle_type)
                    return jsonify({"success": False, 
                                    "message": f"Rollback to package {rollback_package[0]} version {rollback_package[1]} failed.",
                                    "error": str(e)}), 500

        elif action == 'deploy':
            # Deploy the latest uploaded package
            cursor.execute(f"SELECT * FROM {table_name} ORDER BY lastUpdated DESC LIMIT 1")
            latest_package = cursor.fetchone()
            if latest_package:
                local_file = f"/var/deploy/uploads/{bundle_type}_{latest_package[1]}_{latest_package[2]}.tar.gz"
                if not os.path.exists(local_file):
                    return jsonify({"success": False, "error": f"Package file not found: {local_file}"}), 404

                try:
                    deploy_with_scp(local_file, server_name, remote_dir, remote_user, install_script)
                    update_server_history(server_name, latest_package[1], latest_package[2])
                    update_bundle_status(latest_package[0], latest_package[1], latest_package[2], "Pass", bundle_type=bundle_type)
                    return jsonify({
                        "success": True,
                        "message": f"Deployment of latest package {latest_package[1]} version {latest_package[2]} completed successfully."
                    })
                except Exception as e:
                    logging.error(f"Deployment failed: {e}")
                    update_bundle_status(latest_package[0], latest_package[1], latest_package[2], "Fail", bundle_type=bundle_type)
                    return jsonify({"success": False, 
                                    "message": f"Deployment of latest package {latest_package[1]} version {latest_package[2]} failed.",
                                    "error": str(e)}), 500

    except Exception as e:
        logging.error(f"Error during deployment action '{action}': {e}")
        return jsonify({"success": False, "error": str(e)}), 500
    finally:
        cursor.close()



def deploy_with_scp(local_file, remote_host, remote_dir, username, install_script):
    print(f"Transferring {local_file} to {remote_host}:{remote_dir}...")
    subprocess.run(
        ["scp", local_file, f"{username}@{remote_host}:{remote_dir}"],
        check=True
    )
    print(f"File transferred successfully to {remote_host}:{remote_dir}")

    remote_file_path = posixpath.join(remote_dir, os.path.basename(local_file))
    extracted_dir_name = os.path.basename(local_file).rsplit('.', 2)[0]
    extracted_dir_path = posixpath.join(remote_dir, extracted_dir_name)

    commands = [
        f"tar -xvf {remote_file_path} -C {remote_dir}",
        f"cd {extracted_dir_path} && bash {install_script}"
    ]

    for cmd in commands:
        print(f"Executing remote command: {cmd}")
        subprocess.run(
            ["ssh", f"{username}@{remote_host}", cmd],
            check=True
        )
    print("Deployment completed successfully!")



@app.route('/help', methods=['GET'])
def api_help():
    help_content = {
        "endpoints": {
            "/database": {
                "method": "GET",
                "description": "Fetch the current database name and list of tables.",
                "parameters": None
            },
            "/database/<table_name>": {
                "method": "GET",
                "description": "Fetches all rows from the specified table with key-value pairs for each column and value.",
                "example": "/database/frontend_bundles",
                "parameters": None
            },
            "/update_bundle": {
                "method": "POST",
                "description": "Update the status of a bundle in the database.",
                "parameters": {
                    "bundle_name": "string (required)",
                    "version": "string (required)",
                    "status": "string (required, e.g., New, Pass, Fail)",
                    "notes": "string (optional)"
                }
            },
            "/upload": {
                "method": "POST",
                "description": "Upload a tarball to the server and update its database entry.",
                "parameters": {
                    "file": "file (required, tarball with specific naming format)",
                    "id": "integer (required)",
                    "notes": "string (optional)"
                }
            },
            "/deploy/dev/<server_name>": {
                "method": "POST",
                "description": "Perform deployment actions (deploy, latest, or rollback) on a specified development server.",
                "parameters": {
                    "server_name": "string (required, server identifier)",
                    "action": "string (required, e.g., deploy, latest, rollback)",
                    "bundle_name": "string (required for rollback)",
                    "version": "string (required for rollback)",
                    "bundle_type": "string (optional, e.g., frontend, backend, dmz)"
                }
            },
            "/management/database": {
                "method": "POST",
                "description": "Execute SQL queries on the specified database.",
                "parameters": {
                    "database": "string (required)",
                    "query": "string (required)"
                }
            },
            "/management/uploads": {
                "method": "POST",
                "description": "Manage uploaded files (clear, list, or delete).",
                "parameters": {
                    "action": "string (required, one of 'clear', 'list', 'delete')",
                    "file": "string (required for 'delete')"
                }
            },
            "/help": {
                "method": "GET",
                "description": "Get a list of all available endpoints and their details.",
                "parameters": None
            }
        }
    }
    return jsonify(help_content)



# Main entry point
if __name__ == '__main__':
    # Remove debug=True in production
    app.run(host='0.0.0.0', port=5000, debug=True)
