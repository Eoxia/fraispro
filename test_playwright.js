const { chromium } = require('playwright');
const path = require('path');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();
  
  try {
      console.log('Navigating to app.php with bypass...');
      await page.goto('http://localhost/dolibarr23/htdocs/custom/fraispro/view/frontend/app.php?test_bypass=1');
      
      console.log('Setting file to upload...');
      const filePath = path.resolve('C:/wamp64/www/dolibarr23/htdocs/custom/fraispro/test_image.jpg');
      await page.setInputFiles('input#fast_take-photo', filePath);

      console.log('Waiting for cropper to open and clicking save...');
      await page.waitForSelector('#saturne-btn-save-photo', { state: 'visible', timeout: 5000 });
      await page.click('#saturne-btn-save-photo');

      console.log('Waiting for AJAX upload to complete...');
      const response = await page.waitForResponse(
          res => res.url().includes('action=uploadPhoto') && res.request().method() === 'POST'
      );
      
      console.log('Upload status:', response.status());
      const text = await response.text();
      console.log('Upload response body:', text);
  } catch (e) {
      console.error(e);
      await page.screenshot({ path: 'C:/wamp64/www/dolibarr23/htdocs/custom/fraispro/pw_error2.png' });
  } finally {
      await browser.close();
      console.log('Test completed successfully.');
  }
})();
