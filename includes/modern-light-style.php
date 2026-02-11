<style>
:root {
    --primary: #4F46E5;
    --primary-hover: #4338CA;
    --bg-light: #F9FAFB;
    --card-bg: #FFFFFF;
    --text-dark: #111827;
    --text-gray: #6B7280;
    --border: #E5E7EB;
    --shadow: 0 1px 3px rgba(0,0,0,0.1);
    --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: var(--bg-light);
    color: var(--text-dark);
    line-height: 1.6;
}

.container {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 2rem;
    box-shadow: var(--shadow-lg);
    margin-top: 2rem;
}

h1, h2, h3 {
    color: var(--text-dark);
    font-weight: 600;
}

.btn-primary, .btn {
    background: var(--primary);
    color: white;
    border: none;
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    transition: all 0.2s;
}

.btn-primary:hover, .btn:hover {
    background: var(--primary-hover);
    transform: translateY(-1px);
}

.table {
    background: var(--card-bg);
    border-radius: 8px;
    overflow: hidden;
}

.table thead {
    background: var(--bg-light);
}

input, select, textarea {
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 0.6rem;
}

input:focus, select:focus, textarea:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
}
</style>
