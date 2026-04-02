import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  timeout: 30000,
  retries: 1,
  use: {
    baseURL: process.env.BASE_URL || 'http://nginx:8080',
    screenshot: 'only-on-failure',
    trace: 'on-first-retry',
  },
  reporter: [['list']],
});
