# Setting Up Rsyslog Using UDP

## Server Configuration

1. **Install Rsyslog**
    ```bash
    sudo apt-get update
    sudo apt-get install rsyslog
    ```

2. **Configure Rsyslog to Receive Logs via UDP**
    - Open the Rsyslog configuration file:
        ```bash
        sudo nano /etc/rsyslog.conf
        ```
    - Uncomment the following lines to enable UDP reception:
        ```
        # provides UDP syslog reception
        module(load="imudp")
        input(type="imudp" port="514")
        ```
    - Open the Rsyslog configuration file it may look similar or not the same. Look for config file that has default in the name.
        ```bash
        sudo nano /etc/rsyslog.d/50-default.conf
        ```
    - Uncomment the following lines to enable UDP reception for certain log types:
        ```
        #
        # First some standard log files.  Log by facility.
        #
        auth,authpriv.*                 /var/log/auth.log
        *.*;auth,authpriv.none          -/var/log/syslog
        #cron.*                         /var/log/cron.log
        daemon.*                        -/var/log/daemon.log
        kern.*                          -/var/log/kern.log
        #lpr.*                          -/var/log/lpr.log
        mail.*                          -/var/log/mail.log
        #user.*                         -/var/log/user.log

        ```

3. **Restart Rsyslog Service**
    ```bash
    sudo systemctl restart rsyslog
    ```

4. **Open UDP Port 514 in Firewall**
If ufw rules are setup on machine recieving the logs
    ```bash
    sudo ufw allow 514/udp
    ```

## Client Configuration

1. **Install Rsyslog**
    ```bash
    sudo apt-get update
    sudo apt-get install rsyslog
    ```

2. **Configure Rsyslog to Send Logs to Server**
    - Open the Rsyslog configuration default file like we did for the server earlier:
        ```bash
        sudo nano /etc/rsyslog.d/50-default.conf
        ```
    - Add the following line at the end of the file to forward logs to the server change `10.100.0.2` to the server that will aggregate the logs:
        ```
        #
        # First some standard log files.  Log by facility.
        #
        $template RemoteLogs,"<%PRI%>%TIMESTAMP% %HOSTNAME% %syslogtag%%msg%"
        auth,authpriv.*                 /var/log/auth.log
        auth,authpriv.*                 @10.100.0.2:514;RemoteLogs
        *.*;auth,authpriv.none          -/var/log/syslog
        *.*;auth,authpriv.none          @10.100.0.2:514;RemoteLogs
        #cron.*                         /var/log/cron.log
        daemon.*                        @10.100.0.2:514;RemoteLogs
        kern.*                          -/var/log/kern.log
        kern.*                          @10.100.0.2:514;RemoteLogs
        #lpr.*                          -/var/log/lpr.log
        #mail.*                         -/var/log/mail.log
        #user.*                         -/var/log/user.log

        ```

3. **Restart Rsyslog Service**
    ```bash
    sudo systemctl restart rsyslog
    ```

## Verification

1. **Check Server Logs**
    - On the server, check the logs to ensure they are being received from the client:
        ```bash
        sudo tail -f /var/log/syslog
        ```
    - Output should look similar to this:
        ```bash
        root@zt-server-01:/var/log# tail -f syslog
        2024-11-22T05:06:09.143255+00:00 zt-server-01 systemd-resolved[547]: Using degraded feature set TCP instead of UDP for DNS server 67.207.67.2.
        2024-11-22T05:06:09.249913+00:00 zt-server-01 systemd[1]: apt-news.service: Deactivated successfully.
        2024-11-22T05:06:09.250255+00:00 zt-server-01 systemd[1]: Finished apt-news.service - Update APT News.
        2024-11-22T05:06:09.258791+00:00 zt-server-01 systemd[1]: esm-cache.service: Deactivated successfully.
        2024-11-22T05:06:09.258970+00:00 zt-server-01 systemd[1]: Finished esm-cache.service - Update the local ESM caches.
        2024-11-22T05:06:09.752002+00:00 zt-server-01 dbus-daemon[902]: [system] Activating via systemd: service name='org.freedesktop.PackageKit' unit='packagekit.service' requested by ':1.49' (uid=0 pid=5799 comm="/usr/bin/gdbus call --system --dest org.freedeskto" label="unconfined")
        2024-11-22T05:06:09.755050+00:00 zt-server-01 systemd[1]: Starting packagekit.service - PackageKit Daemon...
        2024-11-22T05:06:09.775830+00:00 zt-server-01 PackageKit: daemon start
        2024-11-22T05:06:09.792090+00:00 zt-server-01 dbus-daemon[902]: [system] Successfully activated service 'org.freedesktop.PackageKit'
        2024-11-22T05:06:09.792195+00:00 zt-server-01 systemd[1]: Started packagekit.service - PackageKit Daemon.
        ```

2. **Send Test Log from Client**
    - On the client, send a test log message:
        ```bash
        logger "Test message from client"
        ```

3. **Verify Test Log on Server**
    - Confirm that the test message appears in the server logs.

        ```bash
        root@zt-server-01:/var/log# tail -f syslog
        2024-11-22T05:06:09.143255+00:00 zt-server-01 systemd-resolved[547]: Using degraded feature set TCP instead of UDP for DNS server 67.207.67.2.
        2024-11-22T05:06:09.249913+00:00 zt-server-01 systemd[1]: apt-news.service: Deactivated successfully.
        2024-11-22T05:06:09.250255+00:00 zt-server-01 systemd[1]: Finished apt-news.service - Update APT News.
        2024-11-22T05:06:09.258791+00:00 zt-server-01 systemd[1]: esm-cache.service: Deactivated successfully.
        2024-11-22T05:06:09.258970+00:00 zt-server-01 systemd[1]: Finished esm-cache.service - Update the local ESM caches.
        2024-11-22T05:06:09.752002+00:00 zt-server-01 dbus-daemon[902]: [system] Activating via systemd: service name='org.freedesktop.PackageKit' unit='packagekit.service' requested by ':1.49' (uid=0 pid=5799 comm="/usr/bin/gdbus call --system --dest org.freedeskto" label="unconfined")
        2024-11-22T05:06:09.755050+00:00 zt-server-01 systemd[1]: Starting packagekit.service - PackageKit Daemon...
        2024-11-22T05:06:09.775830+00:00 zt-server-01 PackageKit: daemon start
        2024-11-22T05:06:09.792090+00:00 zt-server-01 dbus-daemon[902]: [system] Successfully activated service 'org.freedesktop.PackageKit'
        2024-11-22T05:06:09.792195+00:00 zt-server-01 systemd[1]: Started packagekit.service - PackageKit Daemon.
        2024-11-22T05:07:09+00:00 test-server-01 root: Test syslog message from client
        ```
