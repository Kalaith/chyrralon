import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig(() => {
  let base = '/chyrralon/';

  if (process.env.VITE_BASE_PATH) {
    base = process.env.VITE_BASE_PATH;
  }

  return {
    base,
    plugins: [react()],
  };
});
