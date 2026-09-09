import paramiko
import time
import socket
import select
import threading

def reverse_forward_tunnel(server_port, remote_host, remote_port, transport):
    transport.request_port_forward("", server_port)
    while True:
        chan = transport.accept(1000)
        if chan is None:
            continue
        thr = threading.Thread(
            target=handler, args=(chan, remote_host, remote_port)
        )
        thr.setDaemon(True)
        thr.start()

def handler(chan, host, port):
    sock = socket.socket()
    try:
        sock.connect((host, port))
    except Exception as e:
        chan.close()
        return

    while True:
        r, w, x = select.select([sock, chan], [], [])
        if sock in r:
            data = sock.recv(1024)
            if len(data) == 0:
                break
            chan.send(data)
        if chan in r:
            data = chan.recv(1024)
            if len(data) == 0:
                break
            sock.send(data)
    chan.close()
    sock.close()

if __name__ == '__main__':
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    # Connected to Active Bot VPS
    vps_ip = '187.52.121.55'
    vps_port = 22
    vps_user = 'root'
    vps_password = 'Mansur@78123'

    print(f"Connecting SSH tunnel to VPS {vps_ip}...")
    client.connect(vps_ip, vps_port, vps_user, vps_password)
    # Ensure remote port 5000 is clean and available
    stdin, stdout, stderr = client.exec_command('fuser -k 5000/tcp 2>/dev/null || true')
    stdout.read()
    time.sleep(0.5)

    print("✅ SSH Reverse Tunnel established: VPS port 5000 <-> Local PC MT5 Bridge port 5000")
    print("🚀 Keep this window OPEN while trading bot is active.")
    reverse_forward_tunnel(5000, '127.0.0.1', 5000, client.get_transport())
