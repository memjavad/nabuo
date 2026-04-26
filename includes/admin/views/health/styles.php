<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<style>
:root {
--naboo-primary: #4f46e5;
--naboo-primary-dark: #4338ca;
--naboo-success: #10b981;
--naboo-warning: #f59e0b;
--naboo-danger: #ef4444;
--naboo-slate-50: #f8fafc;
--naboo-slate-100: #f1f5f9;
--naboo-slate-200: #e2e8f0;
--naboo-slate-500: #64748b;
--naboo-slate-800: #1e293b;
--naboo-slate-900: #0f172a;
--naboo-radius: 12px;
--naboo-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
--naboo-shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
}

.naboo-health-wrapper {
font-family: 'Inter', sans-serif;
color: var(--naboo-slate-800);
max-width: 1200px;
margin: 20px auto;
padding: 0 20px;
}

.naboo-health-header {
display: flex;
justify-content: space-between;
align-items: center;
margin-bottom: 40px;
padding: 40px;
background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
border-radius: 16px;
color: white;
box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
position: relative;
overflow: hidden;
}

.naboo-health-header::after {
content: '';
position: absolute;
top: -50px;
right: -50px;
width: 200px;
height: 200px;
background: rgba(16, 185, 129, 0.1);
filter: blur(80px);
border-radius: 50%;
}

.naboo-header-info h1 {
color: white !important;
font-size: 32px !important;
font-weight: 800 !important;
margin: 0 !important;
display: flex;
align-items: center;
gap: 16px;
letter-spacing: -0.025em;
}

.naboo-header-info h1 span {
background: rgba(255,255,255,0.1);
width: 56px;
height: 56px;
display: flex;
align-items: center;
justify-content: center;
border-radius: 14px;
backdrop-filter: blur(4px);
border: 1px solid rgba(255,255,255,0.1);
}

.naboo-header-info p {
color: var(--naboo-slate-200) !important;
margin: 0 !important;
font-size: 14px;
opacity: 0.8;
}

.naboo-health-grid {
display: grid;
grid-template-columns: 1.5fr 1fr;
gap: 24px;
}

@media (max-width: 1024px) {
.naboo-health-grid { grid-template-columns: 1fr; }
}

.naboo-glass-card {
background: white;
border-radius: var(--naboo-radius);
border: 1px solid var(--naboo-slate-200);
box-shadow: var(--naboo-shadow);
overflow: hidden;
transition: all 0.3s ease;
}

.naboo-glass-card:hover {
box-shadow: var(--naboo-shadow-lg);
transform: translateY(-2px);
}

.card-header {
padding: 20px 24px;
border-bottom: 1px solid var(--naboo-slate-100);
display: flex;
align-items: center;
gap: 12px;
background: var(--naboo-slate-50);
}

.card-header h3 {
margin: 0 !important;
font-size: 18px !important;
font-weight: 700 !important;
color: var(--naboo-slate-900);
}

.card-body {
padding: 24px;
}

/* Status Indicators */
.health-score-ring {
width: 120px;
height: 120px;
margin: 0 auto 20px;
position: relative;
display: flex;
align-items: center;
justify-content: center;
}

.score-circle {
font-size: 36px;
font-weight: 800;
color: var(--naboo-primary);
}

.health-item {
display: flex;
justify-content: space-between;
align-items: center;
padding: 14px 16px;
background: var(--naboo-slate-50);
border-radius: 8px;
margin-bottom: 12px;
border: 1px solid transparent;
transition: all 0.2s ease;
}

.health-item:hover {
border-color: var(--naboo-slate-200);
background: white;
}

.health-item-label {
display: flex;
align-items: center;
gap: 12px;
font-weight: 600;
font-size: 14px;
}

.status-dot {
width: 8px;
height: 8px;
border-radius: 50%;
position: relative;
}

.status-dot::after {
content: '';
position: absolute;
inset: -4px;
border-radius: 50%;
background: inherit;
opacity: 0.2;
animation: pulse 2s infinite;
}

@keyframes pulse {
0% { transform: scale(1); opacity: 0.3; }
70% { transform: scale(2.5); opacity: 0; }
100% { transform: scale(1); opacity: 0; }
}

.status-dot.good { background: var(--naboo-success); }
.status-dot.warning { background: var(--naboo-warning); }
.status-dot.bad { background: var(--naboo-danger); }

.health-item-value {
font-size: 13px;
color: var(--naboo-slate-500);
font-weight: 500;
}

/* Maintenance Actions */
.maintenance-btn-grid {
display: grid;
grid-template-columns: repeat(auto-fill, minmax(100%, 1fr));
gap: 12px;
}

.maintenance-btn-item {
display: flex;
justify-content: space-between;
align-items: center;
padding: 16px;
border: 1px solid var(--naboo-slate-200);
border-radius: 10px;
transition: all 0.2s ease;
}

.maintenance-btn-item:hover {
background: var(--naboo-slate-50);
border-color: var(--naboo-primary);
}

.btn-info h4 { margin: 0 0 2px 0 !important; font-size: 14px !important; color: var(--naboo-slate-900); }
.btn-info p { margin: 0 !important; font-size: 12px; color: var(--naboo-slate-500); }

.naboo-btn-elegant {
background: white !important;
border: 1px solid var(--naboo-slate-200) !important;
color: var(--naboo-slate-900) !important;
border-radius: 6px !important;
font-weight: 600 !important;
padding: 6px 16px !important;
transition: all 0.2s ease !important;
cursor: pointer;
}

.naboo-btn-elegant:hover {
background: var(--naboo-primary) !important;
color: white !important;
border-color: var(--naboo-primary) !important;
}

.naboo-btn-primary {
background: var(--naboo-primary) !important;
border: none !important;
color: white !important;
padding: 10px 24px !important;
border-radius: 8px !important;
font-weight: 700 !important;
box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.4);
}

.naboo-btn-primary:hover {
background: var(--naboo-primary-dark) !important;
transform: translateY(-1px);
}

/* Settings */
.settings-row {
padding: 16px;
background: var(--naboo-slate-50);
border-radius: 10px;
border: 1px dashed var(--naboo-slate-200);
}

#health-results-wrapper {
animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
from { opacity: 0; transform: translateY(10px); }
to { opacity: 1; transform: translateY(0); }
}
</style>
