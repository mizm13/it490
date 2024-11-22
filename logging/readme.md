# Guides to Setup Distributed Logging

## Quick overview
These methods can be installed independently or in conjunction with each other. So you don't have to pick and choose which method would be best for the working virtual environment. These are just a quick overview of each method and how they could be used together.

### rsyslog
>File: rsyslog.md
+ **Install rsyslog**:
   - Rsyslog is a powerful logging system that can forward log messages to various destinations.
   - Install rsyslog on your servers where you want to collect logs.

+ **Configure rsyslog**:
   - Edit the rsyslog configuration file usually located at:
    ```
        /etc/rsyslog.conf 
        or 
        /etc/rsyslog.d/50-default.conf
    ```
   - Set up rsyslog to forward logs to a central log server or directly to RabbitMQ.


### RabbitMQ (fanout) and Filebeat/Logstash
>File: rabbit-fanout.md
+ **Install RabbitMQ**:
   - RabbitMQ is a message broker that allows you to route messages between different systems.
   - Install RabbitMQ on a server that will act as the central message broker.

+ **Configure RabbitMQ**:
   - Set up a RabbitMQ exchange of type `fanout` to broadcast log messages to multiple consumers.
   - Create queues and bind them to the fanout exchange.

+ **Install Filebeat or Logstash**:
   - Filebeat and Logstash are tools from the Elastic Stack that can collect, parse, and forward logs.
   - Install Filebeat or Logstash on the servers where you want to collect logs.

+ **Configure Filebeat/Logstash**:
   - Configure Filebeat or Logstash to read log files and forward them to RabbitMQ.
   - Ensure that the configuration points to the RabbitMQ exchange you set up.

+ **Set Up Log Consumers**:
   - Set up applications or services that will consume logs from RabbitMQ.
   - These consumers can process, store, or analyze the logs as needed.

+ **Test the Setup**:
   - Generate some log messages and verify that they are correctly forwarded through rsyslog, RabbitMQ, and received by the consumers.
   - Check logs at each step to ensure everything is working as expected.

### Elasticsearch and Filebeat/Logstash  
>File: elasticsearch.md  

+ **Install Elasticsearch**:  
   - Elasticsearch is a search and analytics engine that stores logs in a way that's easy to search and analyze.  
   - Install Elasticsearch on a central server where all logs will be aggregated.  

+ **Install Filebeat or Logstash**:  
   - Filebeat and Logstash are tools from the Elastic Stack that collect and process logs.  
   - Install Filebeat on servers where logs are generated or Logstash if you need advanced log processing.  

+ **Configure Filebeat**:  
   - Set up Filebeat to monitor log files on your servers.  
   - Configure Filebeat to send logs directly to Elasticsearch or to Logstash for further processing.  

+ **Set Up Logstash (Optional)**:  
   - Use Logstash to process logs if you need advanced filtering or enrichment.  
   - Configure Logstash to receive logs from Filebeat, process them (e.g., parse JSON, filter fields), and forward them to Elasticsearch.  

+ **Visualize with Kibana**:  
   - Install Kibana, a web-based dashboard, to visualize logs stored in Elasticsearch.  
   - Use Kibana to search logs, create alerts, and generate reports.  

+ **Test the Setup**:  
   - Generate sample logs on the server(s).  
   - Verify that Filebeat or Logstash forwards the logs to Elasticsearch.  
   - Confirm that logs are searchable and viewable in Kibana.  

+ **Scale Up**:  
   - Add more Filebeat instances to collect logs from additional serv
