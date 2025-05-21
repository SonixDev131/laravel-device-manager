export interface CommandHistoryItem {
    id: string;
    type: string;
    status: string;
    created_at: string;
    completed_at: string | null;
    target: string;
    is_group_command: boolean;
    error?: string;
}
