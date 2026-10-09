const puppeteer = require('puppeteer');

(async () => {
  const browser = await puppeteer.launch();
  const page = await browser.newPage();
  
  page.on('console', msg => console.log('BROWSER LOG:', msg.text()));
  page.on('pageerror', error => console.log('BROWSER ERROR:', error.message));
  
  console.log('Navigating to login...');
  await page.goto('http://127.0.0.1:8000/login');
  
  await page.type('input[type="email"]', 'admin@example.com');
  await page.type('input[type="password"]', 'password');
  await page.click('button[type="submit"]');
  
  await page.waitForNavigation();
  
  console.log('Navigating to /holidays...');
  await page.goto('http://127.0.0.1:8000/holidays');
  
  await page.waitForTimeout(3000); // wait for render
  
  console.log('Done.');
  await browser.close();
})();
