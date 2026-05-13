document.addEventListener('DOMContentLoaded', () => {
  const toast = document.getElementById('siteToast');
  const form = document.querySelector('.auth-login-form');

  function showToast(message, type = 'error') {
    if (!toast) return;
    toast.textContent = message;
    toast.className = 'site-toast show ' + type;
    setTimeout(() => { toast.className = 'site-toast'; }, 3500);
  }

  const params = new URLSearchParams(window.location.search);
  const durum = params.get('durum');
  const messages = {
    bos_alan: 'TC kimlik numarası ve şifre boş bırakılamaz.',
    giris_hata: 'TC kimlik numarası veya şifre hatalı.',
    sistem_hata: 'Sistem hatası oluştu. Lütfen daha sonra tekrar deneyiniz.',
    oturum_gecersiz: 'Oturum süreniz doldu. Lütfen tekrar giriş yapınız.',
    sifre_degisti: 'Şifreniz değiştiği için tekrar giriş yapmanız gerekiyor.'
  };

  if (durum && messages[durum]) {
    showToast(messages[durum], 'error');
    window.history.replaceState({}, document.title, window.location.pathname);
  }

  if (form) {
    form.addEventListener('submit', (event) => {
      const tc = form.querySelector('input[name="tc_no"]');
      const sifre = form.querySelector('input[name="sifre"]');
      if (!tc.value.trim() || !sifre.value.trim()) {
        event.preventDefault();
        showToast('TC kimlik numarası ve şifre alanlarını doldurunuz.', 'error');
      }
    });
  }
});
