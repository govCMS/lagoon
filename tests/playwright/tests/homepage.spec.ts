import { test, expect } from '@playwright/test';

test.describe('Home Page', () => {
  test('should render the homepage', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveTitle(/GovCMS|Drupal/);
  });

  test('should contain a welcome message', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('body')).toContainText('Welcome to GovCMS');
  });
});
