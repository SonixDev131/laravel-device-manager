<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateInstallationScriptRequest;
use App\Models\InstallationToken;
use App\Models\Room;
use Illuminate\Support\Str;

class InstallationScriptController extends Controller
{
    /**
     * Generate an installation script for the specified operating system.
     */
    public function generate(GenerateInstallationScriptRequest $request)
    {
        $osType = $request->validated('os_type');
        $serverUrl = $request->validated('server_url');
        $roomId = $request->validated('room_id');
        $autoRegister = $request->validated('auto_register', true);

        // Generate installation token for secure registration
        $token = Str::random(64);

        // Store token if room_id is provided
        if ($roomId) {
            $room = Room::query()->find($roomId);

            // Create installation token linked to room
            InstallationToken::query()->create([
                'token' => $token,
                'room_id' => $roomId,
                'expires_at' => now()->addDays(7), // Token valid for 7 days
            ]);
        }

        // Get appropriate script template
        $scriptTemplate = $this->getScriptTemplate($osType);

        // Replace placeholders in the template
        $script = strtr($scriptTemplate, [
            '{{SERVER_URL}}' => $serverUrl,
            '{{ROOM_ID}}' => $roomId ?? '',
            '{{TOKEN}}' => $token,
            '{{AUTO_REGISTER}}' => $autoRegister ? 'true' : 'false',
            '{{TIMESTAMP}}' => now()->timestamp,
        ]);

        return response()->json([
            'script' => $script,
        ]);
    }

    /**
     * Get the script template for the specified operating system.
     */
    private function getScriptTemplate(string $osType): string
    {
        $templatePath = resource_path("scripts/install-agent-{$osType}.template");

        if (file_exists($templatePath)) {
            return file_get_contents($templatePath);
        }

        // Return default templates if files don't exist
        return match ($osType) {
            'windows' => $this->getWindowsScriptTemplate(),
            'mac' => $this->getMacScriptTemplate(),
            default => $this->getLinuxScriptTemplate(),
        };
    }

    /**
     * Get the default Windows PowerShell script template.
     */
    private function getWindowsScriptTemplate(): string
    {
        return <<<'POWERSHELL'
# Computer Management Agent Installation Script
# Generated: {{TIMESTAMP}}
# Server: {{SERVER_URL}}
# Room ID: {{ROOM_ID}}

$ErrorActionPreference = "Stop"
$apiUrl = "{{SERVER_URL}}/api"
$roomId = "{{ROOM_ID}}"
$installToken = "{{TOKEN}}"
$autoRegister = ${{AUTO_REGISTER}}

# Check for admin rights
if (-NOT ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {
    Write-Error "This script must be run as Administrator. Right-click and select 'Run as Administrator'."
    exit 1
}

Write-Host "Installing Computer Management Agent..."

# Create installation directory
$installDir = "$env:ProgramFiles\ComputerAgent"
if (!(Test-Path $installDir)) {
    New-Item -ItemType Directory -Path $installDir | Out-Null
}

# Download agent executable
try {
    $agentUrl = "$apiUrl/download/agent/windows"
    $agentPath = "$installDir\agent.exe"
    Write-Host "Downloading agent from $agentUrl..."
    Invoke-WebRequest -Uri $agentUrl -OutFile $agentPath
} catch {
    Write-Error "Failed to download agent: $_"
    exit 1
}

# Create configuration
$configContent = @"
{
    "server_url": "$apiUrl",
    "room_id": "$roomId",
    "installation_token": "$installToken"
}
"@
$configPath = "$installDir\config.json"
$configContent | Out-File -FilePath $configPath -Encoding utf8

# Create Windows service
Write-Host "Registering agent as a Windows service..."
sc.exe create "ComputerAgent" binPath= "$agentPath" start= auto DisplayName= "Computer Management Agent"
sc.exe description "ComputerAgent" "Allows remote management of this computer"
sc.exe start "ComputerAgent"

# Register computer with the server
if ($autoRegister) {
    Write-Host "Automatically registering computer with server..."
    try {
        # Get computer information
        $computerName = [System.Net.Dns]::GetHostName()
        $ipAddress = (Get-NetIPAddress -AddressFamily IPv4 -InterfaceAlias Ethernet).IPAddress
        if (!$ipAddress) {
            $ipAddress = (Get-NetIPAddress -AddressFamily IPv4 -InterfaceAlias "Wi-Fi").IPAddress
        }

        # Create registration payload
        $registrationBody = @{
            hostname = $computerName
            ip_address = $ipAddress
            os_type = "windows"
            os_version = [System.Environment]::OSVersion.Version.ToString()
            room_id = $roomId
            token = $installToken
        } | ConvertTo-Json

        # Send registration request
        $registrationUrl = "$apiUrl/computers/register"
        $headers = @{
            "Content-Type" = "application/json"
        }

        $response = Invoke-WebRequest -Uri $registrationUrl -Method POST -Body $registrationBody -Headers $headers
        Write-Host "Computer successfully registered!"
    } catch {
        Write-Warning "Failed to auto-register computer: $_"
        Write-Host "The agent is installed and will connect, but registration must be completed manually."
    }
}

Write-Host "Installation completed successfully!"
POWERSHELL;
    }

    /**
     * Get the default Linux Bash script template.
     */
    private function getLinuxScriptTemplate(): string
    {
        return <<<'BASH'
#!/bin/bash
# Computer Management Agent Installation Script
# Generated: {{TIMESTAMP}}
# Server: {{SERVER_URL}}
# Room ID: {{ROOM_ID}}

set -e

API_URL="{{SERVER_URL}}/api"
ROOM_ID="{{ROOM_ID}}"
INSTALL_TOKEN="{{TOKEN}}"
AUTO_REGISTER={{AUTO_REGISTER}}

# Check for root privileges
if [ "$EUID" -ne 0 ]; then
  echo "This script must be run as root (with sudo)"
  exit 1
fi

echo "Installing Computer Management Agent..."

# Create installation directory
INSTALL_DIR="/opt/computer-agent"
mkdir -p "$INSTALL_DIR"

# Download agent binary
echo "Downloading agent..."
AGENT_URL="$API_URL/download/agent/linux"
curl -L "$AGENT_URL" -o "$INSTALL_DIR/agent"
chmod +x "$INSTALL_DIR/agent"

# Create configuration
echo "Creating configuration..."
cat > "$INSTALL_DIR/config.json" << EOF
{
    "server_url": "$API_URL",
    "room_id": "$ROOM_ID",
    "installation_token": "$INSTALL_TOKEN"
}
EOF

# Create systemd service
echo "Creating service..."
cat > "/etc/systemd/system/computer-agent.service" << EOF
[Unit]
Description=Computer Management Agent
After=network.target

[Service]
Type=simple
ExecStart=$INSTALL_DIR/agent
WorkingDirectory=$INSTALL_DIR
Restart=always
RestartSec=10
User=root

[Install]
WantedBy=multi-user.target
EOF

# Enable and start service
systemctl daemon-reload
systemctl enable computer-agent
systemctl start computer-agent

# Register computer with the server
if [ "$AUTO_REGISTER" = true ]; then
    echo "Automatically registering computer with server..."

    # Get computer information
    HOSTNAME=$(hostname)
    IP_ADDRESS=$(hostname -I | awk '{print $1}')
    OS_TYPE="linux"
    OS_VERSION=$(cat /etc/os-release | grep "PRETTY_NAME" | cut -d'"' -f2)

    # Create registration payload
    JSON_PAYLOAD=$(cat <<EOF
{
    "hostname": "$HOSTNAME",
    "ip_address": "$IP_ADDRESS",
    "os_type": "$OS_TYPE",
    "os_version": "$OS_VERSION",
    "room_id": "$ROOM_ID",
    "token": "$INSTALL_TOKEN"
}
EOF
)

    # Send registration request
    REGISTRATION_URL="$API_URL/computers/register"

    if curl -s -X POST "$REGISTRATION_URL" \
        -H "Content-Type: application/json" \
        -d "$JSON_PAYLOAD"; then
        echo "Computer successfully registered!"
    else
        echo "Warning: Failed to auto-register computer"
        echo "The agent is installed and will connect, but registration must be completed manually."
    fi
fi

echo "Installation completed successfully!"
BASH;
    }

    /**
     * Get the default macOS Bash script template.
     */
    private function getMacScriptTemplate(): string
    {
        return <<<'BASH'
#!/bin/bash
# Computer Management Agent Installation Script
# Generated: {{TIMESTAMP}}
# Server: {{SERVER_URL}}
# Room ID: {{ROOM_ID}}

set -e

API_URL="{{SERVER_URL}}/api"
ROOM_ID="{{ROOM_ID}}"
INSTALL_TOKEN="{{TOKEN}}"
AUTO_REGISTER={{AUTO_REGISTER}}

# Check for root privileges
if [ "$EUID" -ne 0 ]; then
  echo "This script must be run as root (with sudo)"
  exit 1
fi

echo "Installing Computer Management Agent..."

# Create installation directory
INSTALL_DIR="/Applications/ComputerAgent"
mkdir -p "$INSTALL_DIR"

# Download agent binary
echo "Downloading agent..."
AGENT_URL="$API_URL/download/agent/mac"
curl -L "$AGENT_URL" -o "$INSTALL_DIR/agent"
chmod +x "$INSTALL_DIR/agent"

# Create configuration
echo "Creating configuration..."
cat > "$INSTALL_DIR/config.json" << EOF
{
    "server_url": "$API_URL",
    "room_id": "$ROOM_ID",
    "installation_token": "$INSTALL_TOKEN"
}
EOF

# Create launch daemon plist
echo "Creating launch daemon..."
cat > "/Library/LaunchDaemons/com.computeragent.plist" << EOF
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>Label</key>
    <string>com.computeragent</string>
    <key>ProgramArguments</key>
    <array>
        <string>/Applications/ComputerAgent/agent</string>
    </array>
    <key>RunAtLoad</key>
    <true/>
    <key>KeepAlive</key>
    <true/>
    <key>StandardErrorPath</key>
    <string>/var/log/computeragent.log</string>
    <key>StandardOutPath</key>
    <string>/var/log/computeragent.log</string>
</dict>
</plist>
EOF

# Load and start the launch daemon
launchctl load /Library/LaunchDaemons/com.computeragent.plist
launchctl start com.computeragent

# Register computer with the server
if [ "$AUTO_REGISTER" = true ]; then
    echo "Automatically registering computer with server..."

    # Get computer information
    HOSTNAME=$(hostname)
    IP_ADDRESS=$(ipconfig getifaddr en0 || ipconfig getifaddr en1)
    OS_TYPE="mac"
    OS_VERSION=$(sw_vers -productVersion)

    # Create registration payload
    JSON_PAYLOAD=$(cat <<EOF
{
    "hostname": "$HOSTNAME",
    "ip_address": "$IP_ADDRESS",
    "os_type": "$OS_TYPE",
    "os_version": "$OS_VERSION",
    "room_id": "$ROOM_ID",
    "token": "$INSTALL_TOKEN"
}
EOF
)

    # Send registration request
    REGISTRATION_URL="$API_URL/computers/register"

    if curl -s -X POST "$REGISTRATION_URL" \
        -H "Content-Type: application/json" \
        -d "$JSON_PAYLOAD"; then
        echo "Computer successfully registered!"
    else
        echo "Warning: Failed to auto-register computer"
        echo "The agent is installed and will connect, but registration must be completed manually."
    fi
fi

echo "Installation completed successfully!"
BASH;
    }
}
