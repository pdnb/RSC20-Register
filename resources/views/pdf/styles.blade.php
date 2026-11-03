@font-face {
    font-family: 'THSarabunNew';
    font-style: normal;
    font-weight: 400;
    src: url('{{ resource_path('fonts/THSarabunNew.ttf') }}') format('truetype');
}
@font-face {
    font-family: 'THSarabunNew';
    font-style: normal;
    font-weight: 700;
    src: url('{{ resource_path('fonts/THSarabunNew-Bold.ttf') }}') format('truetype');
}
body { font-family: 'THSarabunNew', sans-serif; font-size: 16pt; line-height: 1.05; color: #111; }
table { border-collapse: collapse; width: 100%; }
th, td { border: 1px solid #94a3b8; padding: 2px 5px; vertical-align: top; }
th { background: #dbeafe; font-weight: bold; }
.right { text-align: right; }
.center { text-align: center; }
.muted { color: #475569; }
.page-break { page-break-before: always; }
