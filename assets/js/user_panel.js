document.addEventListener('DOMContentLoaded', () => {
  const menuButtons = document.querySelectorAll('.menu-btn');
  const panels = document.querySelectorAll('.panel');

  function activatePanel(targetId, shouldScroll = true) {
    const targetPanel = document.getElementById(targetId);
    if (!targetPanel) return;

    menuButtons.forEach((btn) => btn.classList.toggle('active', btn.dataset.target === targetId));
    panels.forEach((panel) => panel.classList.toggle('active', panel.id === targetId));

    const url = new URL(window.location.href);
    url.searchParams.set('panel', targetId);
    window.history.replaceState({}, '', url);

    if (shouldScroll) window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  menuButtons.forEach((button) => {
    button.addEventListener('click', () => activatePanel(button.dataset.target));
  });

  const params = new URLSearchParams(window.location.search);
  const requestedPanel = params.get('panel');
  if (requestedPanel) activatePanel(requestedPanel, false);

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
        const textSource = Number.isInteger(columnIndex) && cells[columnIndex] ? cells[columnIndex].textContent : row.textContent;
        const searchableText = textSource.replace(/\s+/g, '').toLocaleLowerCase('tr-TR');
        row.style.display = searchableText.includes(query) ? '' : 'none';
      });
    };
    input.addEventListener('input', filterTable);
    input.addEventListener('search', filterTable);
  });

  const poliklinikSec = document.getElementById('poliklinikSec');
  const doktorSec = document.getElementById('doktorSec');
  if (poliklinikSec && doktorSec) {
    const allOptions = Array.from(doktorSec.options);
    const filterDoctors = () => {
      const selected = poliklinikSec.value;
      allOptions.forEach((option, index) => {
        if (index === 0) return;
        const show = !selected || option.dataset.poliklinik === selected;
        option.hidden = !show;
        option.setAttribute('hidden-by-filter', show ? 'false' : 'true');
      });
      if (doktorSec.selectedOptions[0] && doktorSec.selectedOptions[0].hidden) doktorSec.value = '';
    };
    poliklinikSec.addEventListener('change', filterDoctors);
    filterDoctors();
  }



  document.querySelectorAll('[data-add-medicine]').forEach((button) => {
    button.addEventListener('click', () => {
      const form = button.closest('form');
      const repeater = form ? form.querySelector('[data-medicine-repeater]') : null;
      const firstRow = repeater ? repeater.querySelector('.medicine-row') : null;
      if (!repeater || !firstRow) return;
      const clone = firstRow.cloneNode(true);
      clone.querySelectorAll('input, textarea').forEach((input) => { input.value = ''; });
      clone.querySelectorAll('select').forEach((select) => { select.selectedIndex = 0; });
      repeater.appendChild(clone);
    });
  });

  document.addEventListener('click', (event) => {
    const removeButton = event.target.closest('.medicine-remove');
    if (!removeButton) return;
    const repeater = removeButton.closest('[data-medicine-repeater]');
    const rows = repeater ? repeater.querySelectorAll('.medicine-row') : [];
    if (rows.length <= 1) {
      const row = removeButton.closest('.medicine-row');
      if (row) {
        row.querySelectorAll('input, textarea').forEach((input) => { input.value = ''; });
        row.querySelectorAll('select').forEach((select) => { select.selectedIndex = 0; });
      }
      return;
    }
    removeButton.closest('.medicine-row').remove();
  });

  const randevuSaatSec = document.getElementById('randevuSaatSec');
  const randevuTarihInput = document.querySelector('input[name="randevu_tar"]');
  if (doktorSec && randevuSaatSec && randevuTarihInput) {
    const doluSlotlar = window.doluRandevuSlotlari || {};
    const bugun = window.guncelRandevuTarihi || '';
    const simdi = window.guncelRandevuSaati || '';

    const saatDakikayaCevir = (saat) => {
      const parca = String(saat || '').split(':');
      const saatDegeri = Number(parca[0]);
      const dakikaDegeri = Number(parca[1]);
      if (Number.isNaN(saatDegeri) || Number.isNaN(dakikaDegeri)) return -1;
      return saatDegeri * 60 + dakikaDegeri;
    };

    const simdiDakika = saatDakikayaCevir(simdi);

    const updateAvailableSlots = () => {
      const doktorId = doktorSec.value;
      const tarih = randevuTarihInput.value;
      const busy = new Set(doluSlotlar[doktorId + '|' + tarih] || []);
      const seciliTarihBugun = Boolean(tarih && bugun && tarih === bugun);

      Array.from(randevuSaatSec.options).forEach((option, index) => {
        if (index === 0) return;

        const isBusy = Boolean(doktorId && tarih && busy.has(option.value));
        const gecmisSaat = Boolean(seciliTarihBugun && saatDakikayaCevir(option.value) <= simdiDakika);

        option.disabled = isBusy || gecmisSaat;
        if (isBusy) {
          option.textContent = option.value + ' - Dolu';
        } else if (gecmisSaat) {
          option.textContent = option.value + ' - Geçti';
        } else {
          option.textContent = option.value;
        }
      });

      if (randevuSaatSec.selectedOptions[0] && randevuSaatSec.selectedOptions[0].disabled) {
        randevuSaatSec.value = '';
      }
    };

    doktorSec.addEventListener('change', updateAvailableSlots);
    randevuTarihInput.addEventListener('change', updateAvailableSlots);
    updateAvailableSlots();
  }


  const deleteForms = document.querySelectorAll('.delete-form');
  const confirmOverlay = document.getElementById('confirmOverlay');
  const confirmMessage = document.getElementById('confirmMessage');
  const confirmCancel = document.getElementById('confirmCancel');
  const confirmDelete = document.getElementById('confirmDelete');
  let pendingForm = null;

  function closeConfirm() {
    if (!confirmOverlay) return;
    confirmOverlay.classList.remove('show');
    confirmOverlay.setAttribute('aria-hidden', 'true');
    pendingForm = null;
  }

  deleteForms.forEach((form) => {
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      pendingForm = form;
      if (confirmMessage) confirmMessage.textContent = form.dataset.confirmMessage || 'Bu işlemi yapmak istediğine emin misin?';
      if (confirmOverlay) {
        confirmOverlay.classList.add('show');
        confirmOverlay.setAttribute('aria-hidden', 'false');
      }
    });
  });

  if (confirmCancel) confirmCancel.addEventListener('click', closeConfirm);
  if (confirmDelete) confirmDelete.addEventListener('click', () => { if (pendingForm) pendingForm.submit(); });
  if (confirmOverlay) {
    confirmOverlay.addEventListener('click', (event) => { if (event.target === confirmOverlay) closeConfirm(); });
  }
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeConfirm(); });
});
