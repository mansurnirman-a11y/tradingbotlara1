import paramiko
import sys

hostname = "187.52.121.55"
username = "root"
password = "Mansur@786123"

try:
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(hostname, username=username, password=password, timeout=15)

    # Read the log file contents on VPS
    stdin, stdout, stderr = ssh.exec_command("grep -n -i 'production.ERROR' /var/www/capitalfirst/storage/logs/laravel.log | tail -n 5")
    grep_output = stdout.read().decode('utf-8', errors='replace')
    
    print("Recent errors found in VPS log:")
    print(grep_output)
    
    if grep_output.strip():
        # Get the line number of the last error
        last_line_num = grep_output.strip().split('\n')[-1].split(':')[0]
        print(f"\nFetching error details starting at line {last_line_num}...")
        
        stdin, stdout, stderr = ssh.exec_command(f"tail -n +{last_line_num} /var/www/capitalfirst/storage/logs/laravel.log | head -n 30")
        error_details = stdout.read().decode('utf-8', errors='replace')
        print(error_details)

    ssh.close()

except Exception as e:
    print(f"Error: {e}")
    sys.exit(1)
