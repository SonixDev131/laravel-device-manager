import { useToast } from '@/components/ui/toast/use-toast';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

export const useRoomImport = () => {
    // Toast notifications
    const { toast } = useToast();

    // State
    const fileInput = ref<HTMLInputElement | null>(null);
    const isImportDialogOpen = ref<boolean>(false);
    const isFileSelected = ref<boolean>(false);
    const importError = ref<string | null>(null);
    const fileInputName = ref<string | null>(null);
    const isDragging = ref<boolean>(false);

    // Form
    const importForm = useForm({
        jsonFile: null as File | null,
    });

    // Methods
    const handleFileChange = (event: Event): void => {
        const target = event.target as HTMLInputElement;
        const files = target.files;

        if (!files || files.length === 0) {
            isFileSelected.value = false;
            fileInputName.value = null;
            return;
        }

        importForm.jsonFile = files[0];
        isFileSelected.value = true;
        fileInputName.value = files[0].name;
        importError.value = null;
    };

    const handleDragEnter = (e: DragEvent): void => {
        e.preventDefault();
        e.stopPropagation();
        isDragging.value = true;
    };

    const handleDragOver = (e: DragEvent): void => {
        e.preventDefault();
        e.stopPropagation();
        isDragging.value = true;
    };

    const handleDragLeave = (e: DragEvent): void => {
        e.preventDefault();
        e.stopPropagation();
        isDragging.value = false;
    };

    const handleDrop = (e: DragEvent): void => {
        e.preventDefault();
        e.stopPropagation();
        isDragging.value = false;

        const files = e.dataTransfer?.files;
        if (!files || files.length === 0) return;

        const file = files[0];
        if (file.type !== 'application/json') {
            importError.value = 'Only JSON files are allowed';
            return;
        }

        importForm.jsonFile = file;
        isFileSelected.value = true;
        fileInputName.value = file.name;
        importError.value = null;
    };

    const handleFileRemove = (): void => {
        isFileSelected.value = false;
        fileInputName.value = null;
        importForm.jsonFile = null;
        importError.value = null;

        // Reset the file input for future imports
        if (fileInput.value) fileInput.value.value = '';
    };

    const openImportDialog = (): void => {
        isImportDialogOpen.value = true;
        handleFileRemove();
    };

    const closeImportDialog = (): void => {
        isImportDialogOpen.value = false;
        handleFileRemove();
    };

    const submitImport = (): void => {
        if (!importForm.jsonFile) {
            importError.value = 'Please select a JSON file to import';
            return;
        }

        importForm.post(route('rooms.import'), {
            preserveScroll: true,
            onSuccess: () => {
                toast({
                    title: 'Import Successful',
                    description: 'Rooms and computers were imported successfully',
                    variant: 'default',
                });
                handleFileRemove();
                closeImportDialog();

                // Instead of router.reload(), we'll emit an event and let the parent handle it
                window.dispatchEvent(new CustomEvent('room-imported'));
            },
            onError: (errors) => {
                if (errors.message) {
                    importError.value = errors.message;
                    toast({
                        title: 'Import Failed',
                        description: errors.message,
                        variant: 'destructive',
                    });
                } else {
                    importError.value = 'Failed to import rooms. Please check your JSON format.';
                    toast({
                        title: 'Import Failed',
                        description: 'Please check your JSON format and try again',
                        variant: 'destructive',
                    });
                }
            },
        });
    };

    return {
        // State
        fileInput,
        isImportDialogOpen,
        isFileSelected,
        importError,
        fileInputName,
        isDragging,
        importForm,

        // Methods
        handleFileChange,
        handleDragEnter,
        handleDragOver,
        handleDragLeave,
        handleDrop,
        handleFileRemove,
        openImportDialog,
        closeImportDialog,
        submitImport,
    };
};
