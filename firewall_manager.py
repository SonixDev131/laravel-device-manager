import subprocess
import sys

def get_firewall_status():
    """Get the current status of Windows Firewall for all profiles."""
    try:
        # Run netsh command to get firewall status
        cmd = 'netsh advfirewall show allprofiles state'
        result = subprocess.run(cmd, capture_output=True, text=True, shell=True)
        return result.stdout
    except Exception as e:
        return f"Error checking firewall status: {str(e)}"

def set_firewall_state(enable=True):
    """Enable or disable Windows Firewall for all profiles."""
    try:
        state = "on" if enable else "off"
        cmd = f'netsh advfirewall set allprofiles state {state}'
        # Need administrative privileges to run this command
        result = subprocess.run(cmd, capture_output=True, text=True, shell=True)
        if result.returncode == 0:
            return f"Successfully turned firewall {state}"
        else:
            return f"Error: {result.stderr}"
    except Exception as e:
        return f"Error changing firewall state: {str(e)}"

def main():
    if len(sys.argv) > 1:
        # Handle command line arguments
        command = sys.argv[1].lower()
        if command == "status":
            print(get_firewall_status())
        elif command == "on":
            print(set_firewall_state(True))
        elif command == "off":
            print(set_firewall_state(False))
        else:
            print("Invalid command. Use: status, on, or off")
    else:
        # If no arguments, show status
        print(get_firewall_status())

if __name__ == "__main__":
    main()
