//  TODO: Đọc lại phần Store này
import { router } from '@inertiajs/vue3';
import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useComputerStore = defineStore('computer', () => {
    const selectedComputers = ref<string[]>([]);

    function selectComputers(computers: string[]) {
        selectedComputers.value = computers;
    }

    function toggleComputerSelection(computerId: string) {
        const index = selectedComputers.value.indexOf(computerId);
        if (index === -1) {
            selectedComputers.value.push(computerId);
        } else {
            selectedComputers.value.splice(index, 1);
        }
    }

    function clearSelection() {
        selectedComputers.value = [];
    }

    function executeCommand(commandType: string) {
        if (selectedComputers.value.length === 0) return;

        selectedComputers.value.forEach((computerId) => {
            router.post(
                route('commands.dispatch.computer', {
                    computer_id: computerId,
                }),
                {
                    command_type: commandType.toUpperCase(),
                },
            );
        });
    }

    return {
        selectedComputers,
        selectComputers,
        toggleComputerSelection,
        clearSelection,
        executeCommand,
    };
});
