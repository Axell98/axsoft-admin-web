import './rich-text';

/**
 * Cargador de archivos del gestor (modal "Cargar archivos").
 *
 * Sube los archivos de uno en uno con $wire.$upload para poder mostrar el
 * avance individual, y después pide al componente Livewire que los guarde
 * (storeUpload). Se usa con x-data="uploader({ maxKb, extensions })".
 */
window.uploader = ({ maxKb, extensions }) => ({
    open: false,
    dragging: false,
    running: false,
    items: [],
    nextId: 1,

    get total() {
        return this.items.length;
    },

    get completed() {
        return this.items.filter((item) => item.status === 'done').length;
    },

    get failed() {
        return this.items.filter((item) => item.status === 'error').length;
    },

    init() {
        window.addEventListener('beforeunload', (event) => {
            if (this.running) {
                event.preventDefault();
            }
        });
    },

    show() {
        this.open = true;
    },

    close() {
        this.open = false;

        if (!this.running) {
            this.items = [];
        }
    },

    pick(event) {
        this.add(event.target.files);
        event.target.value = '';
    },

    drop(event) {
        this.dragging = false;
        this.add(event.dataTransfer.files);
    },

    add(fileList) {
        Array.from(fileList).forEach((file) => {
            const item = {
                id: this.nextId++,
                file,
                name: file.name,
                size: file.size,
                progress: 0,
                status: 'queued',
                error: '',
                retryable: false,
            };

            const extension = file.name.includes('.') ? file.name.split('.').pop().toLowerCase() : '';

            if (!extensions.includes(extension)) {
                item.status = 'error';
                item.error = extension ? `Tipo de archivo no permitido (.${extension}).` : 'Tipo de archivo no permitido.';
            } else if (file.size > maxKb * 1024) {
                item.status = 'error';
                item.error = `Supera el máximo permitido (${this.format(maxKb * 1024)}).`;
            }

            this.items.push(item);
        });

        this.run();
    },

    async run() {
        if (this.running) {
            return;
        }

        this.running = true;

        let item;
        while ((item = this.items.find((entry) => entry.status === 'queued'))) {
            await this.send(item);
        }

        this.running = false;
    },

    send(item) {
        item.status = 'uploading';

        return new Promise((resolve) => {
            const fail = (message) => {
                item.status = 'error';
                item.error = message;
                item.retryable = true;
                resolve();
            };

            this.$wire.$upload(
                'upload',
                item.file,
                async () => {
                    try {
                        const result = await this.$wire.storeUpload();

                        if (result && result.ok) {
                            item.status = 'done';
                            item.progress = 100;
                            resolve();
                        } else {
                            fail((result && result.error) || 'No se pudo guardar el archivo.');
                        }
                    } catch (error) {
                        fail('No se pudo guardar el archivo.');
                    }
                },
                () => fail('Falló la subida. Revisa el tamaño del archivo o tu conexión.'),
                (event) => {
                    item.progress = event.detail.progress;
                },
            );
        });
    },

    retry(item) {
        item.status = 'queued';
        item.progress = 0;
        item.error = '';
        this.run();
    },

    remove(item) {
        this.items = this.items.filter((entry) => entry.id !== item.id);
    },

    clearFinished() {
        this.items = this.items.filter((entry) => entry.status === 'queued' || entry.status === 'uploading');
    },

    format(bytes) {
        if (bytes < 1024) {
            return `${bytes} B`;
        }

        const units = ['KB', 'MB', 'GB'];
        let value = bytes / 1024;
        let unit = 0;

        while (value >= 1024 && unit < units.length - 1) {
            value /= 1024;
            unit++;
        }

        return `${value.toFixed(value >= 100 ? 0 : 1)} ${units[unit]}`;
    },
});
