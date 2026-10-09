const http = require('http');

async function check() {
    try {
        const fetch = (await import('node-fetch')).default;
        
        // 1. Get CSRF cookie
        const res1 = await fetch('http://127.0.0.1:8000/sanctum/csrf-cookie', {
            method: 'GET'
        });
        const cookies = res1.headers.raw()['set-cookie'].map(c => c.split(';')[0]).join('; ');
        
        // Extract XSRF-TOKEN
        const xsrfToken = cookies.split(';').find(c => c.trim().startsWith('XSRF-TOKEN=')).split('=')[1];
        
        // 2. Login
        const res2 = await fetch('http://127.0.0.1:8000/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Cookie': cookies,
                'X-XSRF-TOKEN': decodeURIComponent(xsrfToken)
            },
            body: JSON.stringify({
                email: 'admin@example.com',
                password: 'password'
            })
        });
        
        const loginCookies = res2.headers.raw()['set-cookie'] ? res2.headers.raw()['set-cookie'].map(c => c.split(';')[0]).join('; ') : '';
        const allCookies = cookies + '; ' + loginCookies;
        
        // 3. Fetch holidays with Inertia
        const res3 = await fetch('http://127.0.0.1:8000/holidays', {
            headers: {
                'Accept': 'text/html, application/xhtml+xml',
                'X-Inertia': 'true',
                'X-Inertia-Version': '',
                'Cookie': allCookies
            }
        });
        
        const text = await res3.text();
        console.log('Status:', res3.status);
        console.log('Response:', text.substring(0, 1000));
        
    } catch (e) {
        console.error(e);
    }
}
check();
