<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GigSync API Tester - Register & Login</title>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --border: #334155;
            --success: #10b981;
            --error: #ef4444;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        body {
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .container {
            width: 100%;
            max-width: 520px;
        }
        .header {
            text-align: center;
            margin-bottom: 24px;
        }
        .header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 6px;
        }
        .header p {
            font-size: 14px;
            color: var(--text-muted);
        }
        .status-badge {
            display: inline-block;
            background: rgba(16, 185, 129, 0.15);
            color: var(--success);
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
            margin-bottom: 20px;
        }
        .tabs {
            display: flex;
            background: #0f172a;
            border-radius: 10px;
            padding: 4px;
            margin-bottom: 20px;
        }
        .tab {
            flex: 1;
            text-align: center;
            padding: 10px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .tab.active {
            background: var(--primary);
            color: #fff;
        }
        .form-group {
            margin-bottom: 16px;
        }
        label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 6px;
            color: var(--text-muted);
        }
        input, select {
            width: 100%;
            padding: 12px 14px;
            background: #0f172a;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            outline: none;
            transition: border 0.2s;
        }
        input:focus, select:focus {
            border-color: var(--primary);
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 8px;
        }
        .btn:hover {
            background: var(--primary-hover);
        }
        .quick-fill {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }
        .chip {
            background: #334155;
            color: #cbd5e1;
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 6px;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .chip:hover {
            background: #475569;
            border-color: var(--primary);
        }
        .result-box {
            display: none;
            margin-top: 20px;
            background: #090d16;
            border-radius: 8px;
            border: 1px solid var(--border);
            overflow: hidden;
        }
        .result-header {
            padding: 10px 14px;
            font-size: 12px;
            font-weight: 600;
            background: #1e293b;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        pre {
            padding: 14px;
            font-size: 12px;
            color: #38bdf8;
            overflow-x: auto;
            max-height: 250px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 GigSync Live Tester</h1>
            <p>Pengujian Endpoint API Register & Login Langsung ke Database</p>
            <div class="status-badge">● Server Backend Aktif (Laravel 11)</div>
        </div>

        <div class="card">
            <div class="tabs">
                <div class="tab active" id="tabLogin" onclick="switchTab('login')">Masuk (Login)</div>
                <div class="tab" id="tabRegister" onclick="switchTab('register')">Daftar (Register)</div>
            </div>

            <!-- LOGIN FORM -->
            <form id="formLogin" onsubmit="handleLogin(event)">
                <div class="quick-fill">
                    <span style="font-size: 12px; color: var(--text-muted); align-self: center;">Tes Cepat:</span>
                    <button type="button" class="chip" onclick="fillLogin('freelancer@gigsync.com', 'Password123!')">Freelancer Demo</button>
                    <button type="button" class="chip" onclick="fillLogin('client@gigsync.com', 'Password123!')">Client Demo</button>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="loginEmail" required placeholder="nama@telkomuniversity.ac.id">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="loginPassword" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn" id="btnLogin">Kirim Request Login</button>
            </form>

            <!-- REGISTER FORM -->
            <form id="formRegister" style="display: none;" onsubmit="handleRegister(event)">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" id="regName" required placeholder="Contoh: Zhafran Mufadhdhol">
                </div>
                <div class="form-group">
                    <label>Email Kampus / Pribadi</label>
                    <input type="email" id="regEmail" required placeholder="zhafran@telkomuniversity.ac.id">
                </div>
                <div class="form-group">
                    <label>Peran Pengguna (Role)</label>
                    <select id="regRole">
                        <option value="FREELANCER">FREELANCER (Pencari Proyek)</option>
                        <option value="CLIENT">CLIENT (Pemberi Kerja)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Password (Min. 8 Karakter)</label>
                    <input type="password" id="regPassword" required placeholder="Password123!">
                </div>
                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <input type="password" id="regPasswordConfirmation" required placeholder="Password123!">
                </div>
                <button type="submit" class="btn" id="btnRegister">Kirim Request Register</button>
            </form>

            <!-- RESULT DISPLAY -->
            <div class="result-box" id="resultBox">
                <div class="result-header">
                    <span id="resultStatus">Respons Backend (HTTP 200)</span>
                    <span id="tokenInfo" style="color: var(--success); font-size: 11px;">Token Berhasil Diterbitkan!</span>
                </div>
                <pre><code id="jsonOutput">Menunggu respons...</code></pre>
            </div>
        </div>
    </div>

    <script>
        function switchTab(type) {
            const tabLogin = document.getElementById('tabLogin');
            const tabRegister = document.getElementById('tabRegister');
            const formLogin = document.getElementById('formLogin');
            const formRegister = document.getElementById('formRegister');
            const resultBox = document.getElementById('resultBox');
            resultBox.style.display = 'none';

            if (type === 'login') {
                tabLogin.classList.add('active');
                tabRegister.classList.remove('active');
                formLogin.style.display = 'block';
                formRegister.style.display = 'none';
            } else {
                tabRegister.classList.add('active');
                tabLogin.classList.remove('active');
                formRegister.style.display = 'block';
                formLogin.style.display = 'none';
            }
        }

        function fillLogin(email, password) {
            document.getElementById('loginEmail').value = email;
            document.getElementById('loginPassword').value = password;
        }

        async function handleLogin(e) {
            e.preventDefault();
            const btn = document.getElementById('btnLogin');
            btn.disabled = true;
            btn.innerText = 'Mengirim...';

            const payload = {
                email: document.getElementById('loginEmail').value,
                password: document.getElementById('loginPassword').value
            };

            try {
                const res = await fetch('/api/v1/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                showResult(res.status, data);
            } catch (err) {
                showResult(500, { error: err.message });
            } finally {
                btn.disabled = false;
                btn.innerText = 'Kirim Request Login';
            }
        }

        async function handleRegister(e) {
            e.preventDefault();
            const btn = document.getElementById('btnRegister');
            btn.disabled = true;
            btn.innerText = 'Mendaftarkan...';

            const payload = {
                name: document.getElementById('regName').value,
                email: document.getElementById('regEmail').value,
                activeRole: document.getElementById('regRole').value,
                password: document.getElementById('regPassword').value,
                passwordConfirmation: document.getElementById('regPasswordConfirmation').value
            };

            try {
                const res = await fetch('/api/v1/auth/register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                showResult(res.status, data);
            } catch (err) {
                showResult(500, { error: err.message });
            } finally {
                btn.disabled = false;
                btn.innerText = 'Kirim Request Register';
            }
        }

        function showResult(status, data) {
            const box = document.getElementById('resultBox');
            const statusLabel = document.getElementById('resultStatus');
            const tokenInfo = document.getElementById('tokenInfo');
            const output = document.getElementById('jsonOutput');

            box.style.display = 'block';
            statusLabel.innerText = `Respons Backend (HTTP ${status})`;
            if (status >= 200 && status < 300) {
                statusLabel.style.color = '#10b981';
                tokenInfo.style.display = 'inline';
                tokenInfo.innerText = data.data?.token ? '✓ Bearer Token Diterbitkan' : '✓ Berhasil';
            } else {
                statusLabel.style.color = '#ef4444';
                tokenInfo.style.display = 'inline';
                tokenInfo.innerText = '✗ Terjadi Kesalahan';
                tokenInfo.style.color = '#ef4444';
            }
            output.innerText = JSON.stringify(data, null, 2);
        }
    </script>
</body>
</html>
