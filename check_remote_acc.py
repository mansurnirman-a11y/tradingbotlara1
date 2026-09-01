import paramiko

host = "187.52.121.55"
port = 22
password = "Mansur@78123"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(hostname=host, port=port, username="root", password=password, timeout=10)

cmd = """cat << 'EOF' > /var/www/capitalfirst/check_acc30.php
<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();

$acc = App\\Models\\BrokerAccount::latest()->first();
echo "Latest Account:\n";
echo "ID: " . $acc->id . "\n";
echo "User ID: " . $acc->user_id . "\n";
echo "Broker: " . $acc->broker . "\n";
echo "Server: " . ($acc->server_name ?? 'N/A') . "\n";
echo "Label: " . $acc->account_label . "\n";
echo "Meta Account ID: " . ($acc->meta_account_id ?? 'N/A') . "\n";
echo "API Key (Login): " . $acc->api_key . "\n";

$exchange = new App\\Services\\ExchangeService($acc);
$bal = $exchange->fetchBalance();
echo "\nfetchBalance() output:\n";
print_r($bal);

echo "\ngetAvailableBalance() output: " . $exchange->getAvailableBalance() . "\n";

EOF
cd /var/www/capitalfirst && php check_acc30.php && rm check_acc30.php
"""

stdin, stdout, stderr = client.exec_command(cmd)
print(stdout.read().decode('utf-8', errors='ignore'))
print(stderr.read().decode('utf-8', errors='ignore'))
client.close()
