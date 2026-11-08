(function () {
  const ed = document.getElementById('editor'); if (!ed) return;
  try { document.execCommand('styleWithCSS', false, true); } catch (e) {}
  const keep = e => { if (e.target.closest('[data-cmd],[data-ins],#btnTable')) e.preventDefault(); };
  document.addEventListener('mousedown', keep);
  document.addEventListener('click', e => {
    const c = e.target.closest('[data-cmd]');
    if (c) { ed.focus(); document.execCommand(c.dataset.cmd, false, c.dataset.val || null); return; }
    const v = e.target.closest('[data-ins]');
    if (v) { ed.focus(); document.execCommand('insertText', false, v.dataset.ins); return; }
    if (e.target.closest('#btnTable')) {
      ed.focus();
      const r = '<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>';
      document.execCommand('insertHTML', false, '<table style="width:100%;border-collapse:collapse" border="1"><tbody>' + r + r + r + '</tbody></table><p><br></p>');
    }
  });
})();
