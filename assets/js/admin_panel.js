document.addEventListener('DOMContentLoaded', () => {
  const menuButtons = document.querySelectorAll('.menu-btn');
  const panels = document.querySelectorAll('.panel');

  function activatePanel(targetId, shouldScroll = true) {
    const targetPanel = document.getElementById(targetId);
    if (!targetPanel) return;

    menuButtons.forEach((btn) => {
      btn.classList.toggle('active', btn.dataset.target === targetId);
    });

    panels.forEach((panel) => {
      panel.classList.toggle('active', panel.id === targetId);
    });

    if (shouldScroll) {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }

  menuButtons.forEach((button) => {
    button.addEventListener('click', () => {
      activatePanel(button.dataset.target);
    });
  });

  const params = new URLSearchParams(window.location.search);
  const requestedPanel = params.get('panel');
  if (requestedPanel) {
    activatePanel(requestedPanel, false);
  }

  document.querySelectorAll('[data-table-search]').forEach((input) => {
    const filterTable = () => {
      const table = document.querySelector(input.dataset.tableSearch);
      if (!table) return;

      const query = input.value.replace(/\s+/g, '').toLocaleLowerCase('tr-TR');
      const columnIndex = Number(input.dataset.searchColumn);
      const rows = table.querySelectorAll('tbody tr');

      rows.forEach((row) => {
        if (row.querySelector('td[colspan]')) return;

        const cells = row.querySelectorAll('td');
        const textSource = Number.isInteger(columnIndex) && cells[columnIndex]
          ? cells[columnIndex].textContent
          : row.textContent;

        const searchableText = textSource.replace(/\s+/g, '').toLocaleLowerCase('tr-TR');
        row.style.display = searchableText.includes(query) ? '' : 'none';
      });
    };

    input.addEventListener('input', filterTable);
    input.addEventListener('keyup', filterTable);
    input.addEventListener('search', filterTable);
  });

  const deleteForms = document.querySelectorAll('.delete-form');
  const confirmOverlay = document.getElementById('confirmOverlay');
  const confirmMessage = document.getElementById('confirmMessage');
  const confirmCancel = document.getElementById('confirmCancel');
  const confirmDelete = document.getElementById('confirmDelete');
  let pendingDeleteForm = null;

  function closeDeleteConfirm() {
    if (!confirmOverlay) return;
    confirmOverlay.classList.remove('show');
    confirmOverlay.setAttribute('aria-hidden', 'true');
    pendingDeleteForm = null;
  }

  deleteForms.forEach((form) => {
    form.addEventListener('submit', (event) => {
      event.preventDefault();

      pendingDeleteForm = form;

      if (confirmMessage) {
        confirmMessage.textContent = form.dataset.confirmMessage || 'Bu kaydı silmek istediğine emin misin?';
      }

      if (confirmOverlay) {
        confirmOverlay.classList.add('show');
        confirmOverlay.setAttribute('aria-hidden', 'false');
      }
    });
  });

  if (confirmCancel) {
    confirmCancel.addEventListener('click', closeDeleteConfirm);
  }

  if (confirmDelete) {
    confirmDelete.addEventListener('click', () => {
      if (pendingDeleteForm) {
        pendingDeleteForm.submit();
      }
    });
  }

  if (confirmOverlay) {
    confirmOverlay.addEventListener('click', (event) => {
      if (event.target === confirmOverlay) {
        closeDeleteConfirm();
      }
    });
  }

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && confirmOverlay && confirmOverlay.classList.contains('show')) {
      closeDeleteConfirm();
    }
  });
});
