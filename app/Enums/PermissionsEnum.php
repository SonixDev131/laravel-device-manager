<?php

declare(strict_types=1);

namespace App\Enums;

enum PermissionsEnum: string
{
    // Room Management
    case MANAGE_ROOMS = 'manage-rooms';
    case CREATE_ROOMS = 'create-rooms';
    case UPDATE_ROOMS = 'update-rooms';
    case DELETE_ROOMS = 'delete-rooms';
    case VIEW_ROOMS = 'view-rooms';
    case IMPORT_ROOMS = 'import-rooms';

    // Computer Management
    case MANAGE_COMPUTERS = 'manage-computers';
    case VIEW_COMPUTER_STATUS = 'view-computer-status';
    case ADD_COMPUTERS_TO_ROOM = 'add-computers-to-room';

    // Command Permissions
    case SEND_SHUTDOWN_COMMAND = 'send-shutdown-command';
    case SEND_RESTART_COMMAND = 'send-restart-command';
    case SEND_LOCK_COMMAND = 'send-lock-command';
    case SEND_MESSAGE_COMMAND = 'send-message-command';
    case TAKE_SCREENSHOT = 'take-screenshot';
    case BLOCK_WEBSITES = 'block-websites';
    case MANAGE_FIREWALL = 'manage-firewall';
    case SEND_CUSTOM_COMMANDS = 'send-custom-commands';

    // Agent Management
    case UPDATE_ROOM_AGENTS = 'update-room-agents';
    case UPDATE_SYSTEM_AGENTS = 'update-system-agents';
    case UPLOAD_AGENT_PACKAGES = 'upload-agent-packages';
    case MANAGE_AGENT_PACKAGES = 'manage-agent-packages';

    // System Administration
    case VIEW_COMMAND_HISTORY = 'view-command-history';
    case SYSTEM_ADMINISTRATION = 'system-administration';
    case MANAGE_USERS = 'manage-users';
    case ASSIGN_ROLES = 'assign-roles';
    case MANAGE_USER_ROOMS = 'manage-user-rooms';

    /**
     * Get permission label for display
     */
    public function label(): string
    {
        return match ($this) {
            self::MANAGE_ROOMS => 'Manage Rooms',
            self::CREATE_ROOMS => 'Create Rooms',
            self::UPDATE_ROOMS => 'Update Rooms',
            self::DELETE_ROOMS => 'Delete Rooms',
            self::VIEW_ROOMS => 'View Rooms',
            self::IMPORT_ROOMS => 'Import Rooms',

            self::MANAGE_COMPUTERS => 'Manage Computers',
            self::VIEW_COMPUTER_STATUS => 'View Computer Status',
            self::ADD_COMPUTERS_TO_ROOM => 'Add Computers to Room',

            self::SEND_SHUTDOWN_COMMAND => 'Send Shutdown Command',
            self::SEND_RESTART_COMMAND => 'Send Restart Command',
            self::SEND_LOCK_COMMAND => 'Send Lock Command',
            self::SEND_MESSAGE_COMMAND => 'Send Message Command',
            self::TAKE_SCREENSHOT => 'Take Screenshot',
            self::BLOCK_WEBSITES => 'Block Websites',
            self::MANAGE_FIREWALL => 'Manage Firewall',
            self::SEND_CUSTOM_COMMANDS => 'Send Custom Commands',

            self::UPDATE_ROOM_AGENTS => 'Update Room Agents',
            self::UPDATE_SYSTEM_AGENTS => 'Update System Agents',
            self::UPLOAD_AGENT_PACKAGES => 'Upload Agent Packages',
            self::MANAGE_AGENT_PACKAGES => 'Manage Agent Packages',

            self::VIEW_COMMAND_HISTORY => 'View Command History',
            self::SYSTEM_ADMINISTRATION => 'System Administration',
            self::MANAGE_USERS => 'Manage Users',
            self::ASSIGN_ROLES => 'Assign Roles',
            self::MANAGE_USER_ROOMS => 'Manage User Room Assignments',
        };
    }

    /**
     * Get permission category
     */
    public function category(): string
    {
        return match ($this) {
            self::MANAGE_ROOMS, self::CREATE_ROOMS, self::UPDATE_ROOMS, self::DELETE_ROOMS,
            self::VIEW_ROOMS, self::IMPORT_ROOMS => 'Room Management',

            self::MANAGE_COMPUTERS, self::VIEW_COMPUTER_STATUS,
            self::ADD_COMPUTERS_TO_ROOM => 'Computer Management',

            self::SEND_SHUTDOWN_COMMAND, self::SEND_RESTART_COMMAND,
            self::SEND_LOCK_COMMAND, self::SEND_MESSAGE_COMMAND,
            self::TAKE_SCREENSHOT, self::BLOCK_WEBSITES,
            self::MANAGE_FIREWALL, self::SEND_CUSTOM_COMMANDS => 'Commands',

            self::UPDATE_ROOM_AGENTS, self::UPDATE_SYSTEM_AGENTS,
            self::UPLOAD_AGENT_PACKAGES, self::MANAGE_AGENT_PACKAGES => 'Agent Management',

            self::VIEW_COMMAND_HISTORY, self::SYSTEM_ADMINISTRATION,
            self::MANAGE_USERS, self::ASSIGN_ROLES, self::MANAGE_USER_ROOMS => 'Administration',
        };
    }
}
