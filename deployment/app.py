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
QA_DIR = "/var/deploy"
PROD_DIR = "/var/deploy"

# Server IP addresses
QA_IP = {"frontend": "10.8.0.6", "backend": "10.8.0.5", "dmz": "10.8.0.9"}
PROD_IP = {"frontend00": "10.8.0.6", "frontend01": "10.8.0.4", "backend00": "10.8.0.5", "backend01": "10.8.0.7", "dmz00": "10.8.0.9"}

# Logging configuration. Remember to uncomment when in production
logging.basicConfig(
   filename='/var/log/deploy_flask_app.log',
   level=logging.DEBUG,
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

        # Insert record if status
        sql = f"""
            INSERT INTO {table_name} (id, bundleName, versionNumber, status, lastUpdated, notes)
            VALUES (%s, %s, %s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE
                bundleName = VALUES(bundleName),
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


def update_server_history(serverType, bundle_name, version):
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
        """, (serverType, bundle_name, version, last_updated))

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
    connection = get_db_connection()
    if not connection:
        return jsonify({"success": False, "error": "Database connection failed"}), 500
    
    cursor = connection.cursor()

    if 'file' not in request.files:
        logging.error("No file part in the request")
        return jsonify({
            "success": False,
            "error": "No file part in the request",
            "data": request.form.to_dict()
        }), 400

    file = request.files['file']
    packageName = request.form.get('name')
    id = request.form.get('id')
    packageType = request.form.get('type')
    packageDesc = request.form.get('description')

    table_name = {
        "frontend": "frontend_bundles",
        "backend": "backend_bundles",
        "dmz": "dmz_bundles"
    }.get(packageType)

    if not table_name:
        return jsonify({"success": False, "error": f"Invalid bundle type: {packageType}"}), 400

    # Increase version number by 0.01 if package with the same name and version exists in database. If no package found, use base version 1.00
    # And increase the id by 1
    existing_version = check_package_exists(packageName, table_name, cursor)
    if existing_version:
        existing_version_str = str(existing_version)
        version_parts = existing_version_str.split(".")
        version_parts[-1] = f"{int(version_parts[-1]) + 1:02d}"
        packageVersion = ".".join(version_parts)
        # Increment the id by the last digit of the version
        id = int(id) + int(version_parts[-1][-1])
    else:
        packageVersion = "1.00"

    # Save the file to the uploads directory with correct version number to not overwrite existing files
    filename = f"{packageType}_{packageName}_{packageVersion}.tar.gz"
    filepath = os.path.join(UPLOAD_DIR, filename)
    file.save(filepath)
    logging.info(f"File saved to {filepath}")
    
    try: 
        if not id:
            logging.error("Package ID is required for the request")
            return jsonify({"success": False, "error": "Package ID is required for the request"}), 400

        # Check for existing package information in the database
        connection = get_db_connection()
        if not connection:
            return jsonify({"success": False, "error": "Database connection failed"}), 500

        if not table_name:
            return jsonify({"success": False, "error": f"Invalid bundle type: {packageType}"}), 400

        # Update the database with status = "New" and the note
        try:
            update_result = update_bundle_status(id, packageName, packageVersion, "New", notes=packageDesc, bundle_type=packageType)
        except Exception as e:
            logging.error(f"Database update failed: {e}")
            return jsonify({"success": False, "error": f"File uploaded but failed to update database: {str(e)}"}), 500
        
        # Deploy to QA and return the result
        deploy_result = deploy_to_qa(packageType, packageVersion, cursor)
        return deploy_result
    
    except Exception as e:
        logging.error(f"Error saving file: {e}")
        return jsonify({"success": False, "error": str(e)}), 500
    finally:
        cursor.close()

# Route to check if package exists in the database
def check_package_exists(bundle_name, table_name, cursor=None):
    if not cursor:
        connection = get_db_connection()
        if not connection:
            return jsonify({"success": False, "error": "Database connection failed"}), 500
        cursor = connection.cursor()
    # Fetch the latest version from the package with the same name and return the version number. If not found, return base version 1.00
    cursor.execute(f"SELECT versionNumber FROM {table_name} WHERE bundleName = '{bundle_name}' ORDER BY versionNumber DESC LIMIT 1")
    existing_version = cursor.fetchone()
    if existing_version:
        return existing_version[0]
    return None

# Query database if package with new status exists and then deploy to QA (frontend, backend, dmz) machine
def deploy_to_qa(serverType, version, cursor):
    connection = get_db_connection()
    if not connection:
        return jsonify({"success": False, "error": "Database connection failed"}), 500
    table_name = {
        "frontend": "frontend_bundles",
        "backend": "backend_bundles",
        "dmz": "dmz_bundles"
    }.get(serverType)

    server_ip = QA_IP.get(serverType)

    if not table_name:
        return jsonify({"success": False, "error": f"Invalid bundle type: {serverType}"}), 400

    try:
        cursor.execute(f"SELECT * FROM {table_name} WHERE status = 'New' ORDER BY lastUpdated DESC LIMIT 1")
        new_package = cursor.fetchone()

        if new_package:
            local_file = f"/var/deploy/uploads/{serverType}_{new_package[1]}_{new_package[2]}.tar.gz"
            base_directory = f"/var/deploy/{serverType}_{new_package[1]}"
            if not os.path.exists(local_file):
                return jsonify({"success": False, "error": f"Package file not found: {local_file}"}), 404

            try:
                deploy_with_scp(local_file, server_ip, QA_DIR, base_directory, version, "root", "install.sh")
                update_server_history(server_ip, new_package[1], new_package[2])
                update_bundle_status(new_package[0], new_package[1], new_package[2], "Pass", bundle_type=serverType)
                return jsonify({
                    "success": True,
                    "message": f"Deployment of new package {new_package[1]} version {new_package[2]} completed successfully."
                })
            except Exception as e:
                logging.error(f"Deployment failed: {e}")
                update_bundle_status(new_package[0], new_package[1], new_package[2], "Fail", bundle_type=serverType)
                rollback_to_latest_pass(serverType, cursor)
                return jsonify({"success": False, 
                                "message": f"Deployment of new package {new_package[1]} version {new_package[2]} failed. Rollback initiated.",
                                "error": str(e)}), 500
        else:
            return jsonify({"success": False, "error": "No new package found in the database."}), 404

    except Exception as e:
        logging.error(f"Error during deployment to QA: {e}")
        return jsonify({"success": False, "error": str(e)}), 500
    finally:
        cursor.close()

# Route to deploy latest package with status "Pass" to production server
@app.route('/deploy/prod/<serverType>', methods=['POST'])
def deploy_to_prod(serverType):
    
    table = request.form.get('table')
    connection = get_db_connection()
    if not connection:
        return jsonify({"success": False, "error": "Database connection failed"}), 500
    cursor = connection.cursor()

    server_ip = PROD_IP.get(serverType)

    try:
        cursor.execute(f"SELECT * FROM {table}_bundles WHERE status = 'Pass' ORDER BY lastUpdated DESC LIMIT 1")
        latest_package = cursor.fetchone()

        if latest_package:
            local_file = f"/var/deploy/uploads/{table}_{latest_package[1]}_{latest_package[2]}.tar.gz"
            base_directory = f"/var/deploy/{table}_{latest_package[1]}"
            if not os.path.exists(local_file):
                return jsonify({"success": False, "error": f"Package file not found: {local_file}"}), 404

            try:
                deploy_with_scp(local_file, server_ip, PROD_DIR, base_directory, latest_package[2], "root", "install.sh")
                update_server_history(server_ip, latest_package[1], latest_package[2])
                return jsonify({
                    "success": True,
                    "message": f"Deployment of latest package {latest_package[1]} version {latest_package[2]} completed successfully."
                })
            except Exception as e:
                logging.error(f"Deployment failed: {e}")
                return jsonify({"success": False, 
                                "message": f"Deployment of latest package {latest_package[1]} version {latest_package[2]} failed.",
                                "error": str(e)}), 500
        else:
            return jsonify({"success": False, "error": "No package with status 'Pass' found in the database."}), 404

    except Exception as e:
        logging.error(f"Error during deployment to production: {e}")
        return jsonify({"success": False, "error": str(e)}), 500
    finally:
        cursor.close()

def rollback_to_latest_pass(serverType, cursor):
    connection = get_db_connection()
    if not connection:
        return jsonify({"success": False, "error": "Database connection failed"}), 500
    table_name = {
        "frontend": "frontend_bundles",
        "backend": "backend_bundles",
        "dmz": "dmz_bundles"
    }.get(serverType)

    server_ip = QA_IP.get(serverType)

    if not table_name:
        return jsonify({"success": False, "error": f"Invalid bundle type: {serverType}"}), 400

    try:
        cursor.execute(f"SELECT * FROM {table_name} WHERE status = 'Pass' ORDER BY lastUpdated DESC LIMIT 1")
        latest_pass = cursor.fetchone()

        if latest_pass:
            local_file = f"/var/deploy/uploads/{serverType}_{latest_pass[1]}_{latest_pass[2]}.tar.gz"
            base_directory = f"/var/deploy/{serverType}_{latest_pass[1]}"
            if not os.path.exists(local_file):
                return jsonify({"success": False, "error": f"Package file not found: {local_file}"}), 404

            try:
                deploy_with_scp(local_file, server_ip, QA_DIR, base_directory, latest_pass[2], "root", "install.sh")
                update_server_history(server_ip, latest_pass[1], latest_pass[2])
                update_bundle_status(latest_pass[0], latest_pass[1], latest_pass[2], "Pass", bundle_type=serverType)
                return jsonify({
                    "success": True,
                    "message": f"Rollback to latest package {latest_pass[1]} version {latest_pass[2]} completed successfully."
                })
            except Exception as e:
                logging.error(f"Rollback failed: {e}")
                update_bundle_status(latest_pass[0], latest_pass[1], latest_pass[2], "Fail", bundle_type=serverType)
                return jsonify({"success": False, 
                                "message": f"Rollback to latest package {latest_pass[1]} version {latest_pass[2]} failed.",
                                "error": str(e)}), 500
        else:
            return jsonify({"success": False, "error": "No package with status 'Pass' found in the database."}), 404
        
    except Exception as e:
        logging.error(f"Error during rollback: {e}")
        return jsonify({"success": False, "error": str(e)}), 500
    finally:
        cursor.close()

def deploy_with_scp(local_file, remote_host, remote_dir, base_directory, version, username, install_script):
    print(f"Transferring {local_file} to {remote_host}:{remote_dir}...")
    subprocess.run(
        ["scp", local_file, f"{username}@{remote_host}:{remote_dir}"],
        check=True
    )
    print(f"File transferred successfully to {remote_host}:{remote_dir}")

    remote_file_path = posixpath.join(remote_dir, os.path.basename(local_file))

    commands = [
        f"tar -xvf {remote_file_path} -C {remote_dir}",
        f"cd {base_directory} && bash {install_script}"
    ]

    for cmd in commands:
        print(f"Executing remote command: {cmd}")
        subprocess.run(
            ["ssh", f"{username}@{remote_host}", cmd],
            check=True
        )
    return jsonify({"success": True, "message": f"Deployment to {remote_host} completed successfully."}), 200

# Main entry point
if __name__ == '__main__':
    # Remove debug=True in production
    app.run(host='0.0.0.0', port=5000, debug=True)
