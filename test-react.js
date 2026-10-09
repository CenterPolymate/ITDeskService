const puppeteer = require('puppeteer');

(async () => {
  const browser = await puppeteer.launch();
  const page = await browser.newPage();
  
  page.on('console', msg => console.log('PAGE LOG:', msg.text()));
  page.on('pageerror', error => console.log('PAGE ERROR:', error.message));
  page.on('response', response => console.log('RESPONSE:', response.url(), response.status()));

  await page.goto('http://127.0.0.1:8000/login');
  
  await page.type('input[type="email"]', 'admin@example.com');
  await page.type('input[type="password"]', 'password');
  await page.click('button[type="submit"]');
  
  await page.waitForNavigation();
  
  console.log('Navigating to /holidays');
  await page.goto('http://127.0.0.1:8000/holidays');
  
  await page.waitForTimeout(2000);
  
  const content = await page.content();
  console.log('Body length:', content.length);
  
  await browser.close();
})();
