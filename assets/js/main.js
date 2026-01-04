function downloadCSV(filename, rows) {
  const processRow = row => row.map(v => '"' + String(v).replaceAll('"', '""') + '"').join(',');
  const csvContent = rows.map(processRow).join('\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', filename);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

function exportTableToCSV(selector, filename) {
  const table = document.querySelector(selector);
  if (!table) return;
  const rows = [];
  const trs = table.querySelectorAll('tr');
  trs.forEach(tr => {
    const cols = tr.querySelectorAll('th,td');
    const row = [];
    cols.forEach(td => row.push(td.innerText.trim()));
    rows.push(row);
  });
  downloadCSV(filename, rows);
}

function exportTableToPDF(selector, filename) {
  const table = document.querySelector(selector);
  if (!table || typeof window.jspdf === 'undefined') return;
  const doc = new window.jspdf.jsPDF('p', 'pt', 'a4');
  const rows = [];
  const header = [];
  const ths = table.querySelectorAll('thead th');
  ths.forEach(th => header.push(th.innerText.trim()));
  rows.push(header);
  const trs = table.querySelectorAll('tbody tr');
  trs.forEach(tr => {
    const cols = tr.querySelectorAll('td');
    const row = [];
    cols.forEach(td => row.push(td.innerText.trim()));
    rows.push(row);
  });
  let y = 40;
  doc.setFontSize(12);
  rows.forEach((r, i) => {
    let x = 40;
    r.forEach(text => {
      doc.text(String(text), x, y);
      x += 140;
    });
    y += 20;
    if (y > 780) {
      doc.addPage();
      y = 40;
    }
  });
  doc.save(filename);
}

