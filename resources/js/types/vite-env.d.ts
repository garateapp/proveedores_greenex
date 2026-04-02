/// <reference types="vite/client" />

interface ImportMetaEnv {
    readonly VITE_APP_NAME?: string;
    readonly VITE_OCR_WORKERS?: string;
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
}
