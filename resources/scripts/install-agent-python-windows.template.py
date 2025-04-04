#!/usr/bin/env python3
# Computer Management Agent Installation Script
# Generated: {{TIMESTAMP}}
# Server: {{SERVER_URL}}
# Room ID: {{ROOM_ID}}

import os
import sys
import json
import platform
import subprocess
import ctypes
import tempfile
import shutil
import urllib.request
from pathlib import Path

# Configuration
SERVER_URL = "{{SERVER_URL}}/api"
ROOM_ID = "{{ROOM_ID}}"
INSTALL_TOKEN = "{{TOKEN}}"
AUTO_REGISTER = "{{AUTO_REGISTER}}"
INSTALL_DIR = os.path.join(os.environ["ProgramFiles"], "ComputerAgent")


def is_admin() -> bool:
    """Check if the script is running with administrator privileges."""
    try:
        return ctypes.windll.shell32.IsUserAnAdmin() != 0
    except Exception:
        return False


def create_install_directory() -> None:
    """Create the installation directory."""
    print("Creating installation directory...")
    os.makedirs(INSTALL_DIR, exist_ok=True)


def download_agent() -> str:
    """Download the agent binary."""
    print("Downloading agent...")
    agent_url = f"{SERVER_URL}/download/agent/windows"
    agent_path = os.path.join(INSTALL_DIR, "agent.exe")

    try:
        with urllib.request.urlopen(agent_url) as response, open(
            agent_path, "wb"
        ) as out_file:
            shutil.copyfileobj(response, out_file)
        return agent_path
    except Exception as e:
        sys.exit(f"Failed to download agent: {e}")


def create_config() -> None:
    """Create the configuration file."""
    print("Creating configuration file...")
    config = {
        "server_url": SERVER_URL,
        "room_id": ROOM_ID,
        "installation_token": INSTALL_TOKEN,
    }

    config_path = os.path.join(INSTALL_DIR, "config.json")
    with open(config_path, "w") as f:
        json.dump(config, f, indent=4)


def install_windows_service(agent_path: str) -> None:
    """Install and start Windows service."""
    print("Installing Windows service...")

    # Create and start service using sc.exe
    subprocess.run(
        [
            "sc",
            "create",
            "ComputerAgent",
            "binPath=",
            agent_path,
            "start=",
            "auto",
            "DisplayName=",
            "Computer Management Agent",
        ],
        check=True,
        capture_output=True,
    )

    subprocess.run(
        [
            "sc",
            "description",
            "ComputerAgent",
            "Allows remote management of this computer",
        ],
        check=True,
        capture_output=True,
    )

    subprocess.run(["sc", "start", "ComputerAgent"], check=True, capture_output=True)

    print("Service installed and started successfully.")


def register_computer() -> None:
    """Register this computer with the server."""
    if not AUTO_REGISTER:
        print("Auto-registration disabled. Manual registration required.")
        return

    print("Automatically registering computer with server...")

    try:
        import socket
        import platform
        import json
        import urllib.request
        import urllib.error

        # Get system information
        computer_name = socket.gethostname()
        ip_address = socket.gethostbyname(socket.gethostname())

        # Create registration payload
        registration_data = {
            "hostname": computer_name,
            "ip_address": ip_address,
            "os_type": "windows",
            "os_version": platform.version(),
            "room_id": ROOM_ID,
            "token": INSTALL_TOKEN,
        }

        # Send registration request
        registration_url = f"{SERVER_URL}/computers/register"
        headers = {"Content-Type": "application/json"}

        data = json.dumps(registration_data).encode("utf-8")
        req = urllib.request.Request(
            registration_url, data=data, headers=headers, method="POST"
        )

        with urllib.request.urlopen(req) as response:
            print("Computer successfully registered!")

    except Exception as e:
        print(f"Failed to auto-register computer: {e}")
        print(
            "The agent is installed and will connect, but registration must be completed manually."
        )


def main() -> None:
    """Main installation function."""
    print("Computer Management Agent Installation")
    print("-" * 40)

    # Check for admin privileges
    if not is_admin():
        print("This script must be run with administrator privileges.")
        print("Please right-click and select 'Run as administrator'.")
        sys.exit(1)

    try:
        # Installation steps
        create_install_directory()
        agent_path = download_agent()
        create_config()
        install_windows_service(agent_path)
        register_computer()

        print("\nInstallation completed successfully!")

    except Exception as e:
        print(f"Installation failed: {e}")
        sys.exit(1)


if __name__ == "__main__":
    main()
