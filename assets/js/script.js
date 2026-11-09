/**
 * SCRIPT UTAMA - BOOKING WISATA WALINI
 * Fungsi: Hamburger menu, validasi form, interaksi mobile
 */

// Toggle Sidebar Mobile
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.overlay');
    
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
    if (overlay) {
        overlay.classList.toggle('active');
    }
}

// Tutup sidebar saat klik overlay
function closeSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.overlay');
    
    if (sidebar) sidebar.classList.remove('active');
    if (overlay) overlay.classList.remove('active');
}

// Inisialisasi saat DOM siap
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Tutup sidebar saat klik overlay
    const overlay = document.querySelector('.overlay');
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
    
    // 2. Tutup sidebar saat klik link di dalamnya
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        const links = sidebar.querySelectorAll('a');
        links.forEach(link => {
            link.addEventListener('click', closeSidebar);
        });
    }
    
    // 3. Tutup sidebar saat resize ke desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });
    
    // 4. Validasi form - cegah submit kosong
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredInputs = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.style.borderColor = '#dc3545';
                } else {
                    input.style.borderColor = '#e0e0e0';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Mohon lengkapi semua field yang wajib diisi!');
            }
        });
    });
    
    // 5. Smooth scroll untuk anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
    
    // 6. Loading indicator saat submit form
    const submitButtons = document.querySelectorAll('button[type="submit"]');
    submitButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const form = this.closest('form');
            if (form && form.checkValidity()) {
                this.disabled = true;
                this.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...';
            }
        });
    });
    
    // 7. Konfirmasi hapus data
    const deleteLinks = document.querySelectorAll('a[href*="delete="]');
    deleteLinks.forEach(link => {
        if (!link.getAttribute('onclick')) {
            link.addEventListener('click', function(e) {
                if (!confirm('Yakin ingin menghapus data ini?')) {
                    e.preventDefault();
                }
            });
        }
    });
});

// Fungsi helper: Format angka ke Rupiah
function formatRupiah(angka) {
    return 'Rp. ' + parseInt(angka).toLocaleString('id-ID');
}

// Fungsi helper: Validasi email
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}
