import './bootstrap';
import {
    createIcons,
    LayoutDashboard, Users, ClipboardCheck, Building2, BookOpen, LibraryBig, BookCopy,
    BookmarkCheck, HandHeart, GraduationCap, Trophy, TriangleAlert, Medal, FileChartColumn,
    Search, ListFilter, Plus, Pencil, Trash2, Eye, Save, Printer, FileSpreadsheet,
    ChevronDown, ChevronLeft, ChevronRight, ChevronsUpDown, Menu, X, LogOut, Settings, User,
    EllipsisVertical, ArchiveRestore, RotateCcw, CalendarDays, Calendar, Download, Upload,
    Check, CircleAlert, CircleCheck, CircleX, Info, Sparkles, TrendingUp, ArrowLeft, ArrowRight,
    Phone, MapPin, Mail, Lock, IdCard, Image, Star, Award, Clock, ShieldCheck, Heart, Home,
    Filter, FolderOpen, FileText, ChartColumn, LayoutGrid, Bell, Sun, Moon, Notebook,
    ClipboardList, ListChecks, BadgeCheck, RefreshCw, ExternalLink, Activity, Target, Flag,
    Bookmark, Book, Wallet, Gift, Wrench, Database, Globe, Zap, Layers, Hash, KeyRound, Power,
    SlidersHorizontal, Send, Copy, Link, Mic, Camera, Monitor, Laptop, Smartphone, Server,
    HardDrive, Cloud, Wifi, UserPlus, UserCheck, Shield, ShieldAlert, HelpCircle, Loader,
    DollarSign, Percent, Repeat, Shuffle, Scissors, ThumbsUp, UsersRound, ContactRound,
    Pointer, Grab, Smile, AlertTriangle, Building, Trash, Funnel, House, Ellipsis,
} from 'lucide';

/* =====================================================================
   SIMPAS FRONT-END
   Ikon Lucide · Toast · Animasi · Isi Otomatis · Filter Periode
   ===================================================================== */

const ICONS = {
    LayoutDashboard, Users, ClipboardCheck, Building2, BookOpen, LibraryBig, BookCopy,
    BookmarkCheck, HandHeart, GraduationCap, Trophy, TriangleAlert, Medal, FileChartColumn,
    Search, ListFilter, Plus, Pencil, Trash2, Eye, Save, Printer, FileSpreadsheet,
    ChevronDown, ChevronLeft, ChevronRight, ChevronsUpDown, Menu, X, LogOut, Settings, User,
    EllipsisVertical, ArchiveRestore, RotateCcw, CalendarDays, Calendar, Download, Upload,
    Check, CircleAlert, CircleCheck, CircleX, Info, Sparkles, TrendingUp, ArrowLeft, ArrowRight,
    Phone, MapPin, Mail, Lock, IdCard, Image, Star, Award, Clock, ShieldCheck, Heart, Home,
    Filter, FolderOpen, FileText, ChartColumn, LayoutGrid, Bell, Sun, Moon, Notebook,
    ClipboardList, ListChecks, BadgeCheck, RefreshCw, ExternalLink, Activity, Target, Flag,
    Bookmark, Book, Wallet, Gift, Wrench, Database, Globe, Zap, Layers, Hash, KeyRound, Power,
    SlidersHorizontal, Send, Copy, Link, Mic, Camera, Monitor, Laptop, Smartphone, Server,
    HardDrive, Cloud, Wifi, UserPlus, UserCheck, Shield, ShieldAlert, HelpCircle, Loader,
    DollarSign, Percent, Repeat, Shuffle, Scissors, ThumbsUp, UsersRound, ContactRound,
    Pointer, Grab, Smile, AlertTriangle, Building, Trash, Funnel, House, Ellipsis,
};

/** Render seluruh ikon Lucide di halaman. */
export function renderIcons(root = document) {
    createIcons({ icons: ICONS, attrs: { 'stroke-width': 2 } });
    return root;
}

document.addEventListener('DOMContentLoaded', () => {
    renderIcons();
    initSidebar();
    initTopbar();
    initDropdowns();
    initToasts();
    initCountUp();
    initReveal();
    initConfirm();
    initPeriodeFilter();
    initSantriAutofill();
    initPoinPreview();
    initImportModal();
    initServiceWorker();
    initSessionExpiration();
});

/* ------------------------------------------------------------------ */
/* SIDEBAR                                                             */
/* ------------------------------------------------------------------ */
function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    const toggles = document.querySelectorAll('[data-toggle-sidebar]');

    const close = () => {
        sidebar?.classList.remove('open');
        backdrop?.classList.remove('open');
        document.body.style.overflow = '';
    };

    toggles.forEach((btn) => {
        btn.addEventListener('click', () => {
            const open = sidebar.classList.toggle('open');
            backdrop.classList.toggle('open', open);
            document.body.style.overflow = open ? 'hidden' : '';
        });
    });

    backdrop?.addEventListener('click', close);

    // submenu aktivitas (accordion)
    document.querySelectorAll('[data-accordion]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = document.querySelector(btn.dataset.accordion);
            const icon = btn.querySelector('.accordion-icon');
            const open = target.classList.toggle('hidden');
            if (icon) icon.style.transform = open ? 'rotate(-90deg)' : 'rotate(0deg)';
        });
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) close();
    });
}

/* ------------------------------------------------------------------ */
/* TOPBAR (efek saat scroll)                                           */
/* ------------------------------------------------------------------ */
function initTopbar() {
    const topbar = document.getElementById('topbar');
    if (!topbar) return;
    const onScroll = () => topbar.classList.toggle('scrolled', window.scrollY > 8);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
}

/* ------------------------------------------------------------------ */
/* DROPDOWN (menu ⋮)                                                   */
/* ------------------------------------------------------------------ */
function initDropdowns() {
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-dropdown]');

        // tutup semua dropdown lain
        document.querySelectorAll('.dropdown-menu:not(.hidden)').forEach((menu) => {
            if (!trigger || menu.id !== trigger.dataset.dropdown) {
                menu.classList.add('hidden');
            }
        });

        if (trigger) {
            e.preventDefault();
            e.stopPropagation();
            const menu = document.getElementById(trigger.dataset.dropdown);
            menu?.classList.toggle('hidden');
        }
    });
}

/* ------------------------------------------------------------------ */
/* TOAST (bagian 37 - satu notifikasi per aksi)                        */
/* ------------------------------------------------------------------ */
const TOAST_ICONS = {
    success: { icon: 'circle-check', cls: 'text-emerald-600 bg-emerald-50 border-emerald-100' },
    error: { icon: 'circle-x', cls: 'text-rose-600 bg-rose-50 border-rose-100' },
    warning: { icon: 'triangle-alert', cls: 'text-amber-600 bg-amber-50 border-amber-100' },
    info: { icon: 'info', cls: 'text-sky-600 bg-sky-50 border-sky-100' },
};

export function showToast(type, message, title = '') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const cfg = TOAST_ICONS[type] || TOAST_ICONS.info;
    const titles = {
        success: 'Berhasil',
        error: 'Gagal',
        warning: 'Perhatian',
        info: 'Informasi',
    };

    const el = document.createElement('div');
    el.className = `toast-item border ${cfg.cls}`;
    el.innerHTML = `
        <span class="grid place-items-center size-9 shrink-0 rounded-xl bg-white/70 border border-current/10">
            <i data-lucide="${cfg.icon}" class="size-5"></i>
        </span>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-bold text-slate-900">${title || titles[type] || 'Notifikasi'}</p>
            <p class="text-xs text-slate-600 leading-relaxed mt-0.5 break-words">${message}</p>
        </div>
        <button class="text-slate-400 hover:text-slate-700 transition-colors" aria-label="Tutup">
            <i data-lucide="x" class="size-4"></i>
        </button>
        <span class="toast-progress"></span>
    `;

    container.appendChild(el);
    renderIcons(el);

    const remove = () => {
        el.style.transition = 'opacity .3s ease, transform .3s ease';
        el.style.opacity = '0';
        el.style.transform = 'translateX(30px)';
        setTimeout(() => el.remove(), 300);
    };

    el.querySelector('button')?.addEventListener('click', remove);
    setTimeout(remove, 4500);
}

function initToasts() {
    const flash = document.getElementById('flash-toast');
    if (flash) {
        showToast(flash.dataset.type || 'info', flash.dataset.message || '');
        flash.remove();
    }

    // toast error validasi (Laravel $errors)
    const errBox = document.getElementById('flash-errors');
    if (errBox) {
        const first = errBox.dataset.message;
        if (first) showToast('error', first);
        errBox.remove();
    }
}

/* ------------------------------------------------------------------ */
/* COUNT UP (angka statistik beranimasi)                                */
/* ------------------------------------------------------------------ */
function initCountUp() {
    const els = document.querySelectorAll('[data-count]');
    if (!els.length) return;

    const animate = (el) => {
        const target = parseFloat(el.dataset.count);
        const duration = 1100;
        const start = performance.now();
        const prefix = el.dataset.prefix || '';
        const suffix = el.dataset.suffix || '';

        const step = (now) => {
            const p = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - p, 3);
            const value = Math.round(target * eased);
            el.textContent = prefix + value.toLocaleString('id-ID') + suffix;
            if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    };

    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                animate(entry.target);
                io.unobserve(entry.target);
            }
        });
    }, { threshold: 0.4 });

    els.forEach((el) => io.observe(el));
}

/* ------------------------------------------------------------------ */
/* REVEAL SAAT SCROLL                                                  */
/* ------------------------------------------------------------------ */
function initReveal() {
    const els = document.querySelectorAll('[data-reveal]');
    if (!els.length) return;

    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                io.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });

    els.forEach((el) => io.observe(el));
}

/* ------------------------------------------------------------------ */
/* KONFIRMASI HAPUS                                                    */
/* ------------------------------------------------------------------ */
function initConfirm() {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (!window.confirm(form.dataset.confirm)) e.preventDefault();
        });
    });
}

/* ------------------------------------------------------------------ */
/* FILTER PERIODE (Laporan & Ranking)                                  */
/* ------------------------------------------------------------------ */
function initPeriodeFilter() {
    document.querySelectorAll('[data-periode-form]').forEach((form) => {
        const select = form.querySelector('[name="periode"]');
        if (!select) return;

        const apply = () => {
            const value = select.value;
            form.querySelectorAll('[data-periode-field]').forEach((el) => {
                const kinds = el.dataset.periodeField.split(',');
                el.classList.toggle('hidden', !kinds.includes(value));
            });
        };

        select.addEventListener('change', apply);
        apply();
    });
}

/* ------------------------------------------------------------------ */
/* ISI OTOMATIS SANTRI (bagian 8)                                      */
/* ------------------------------------------------------------------ */
function initSantriAutofill() {
    const input = document.getElementById('santri-search');
    const hidden = document.getElementById('santri_id');
    const box = document.getElementById('santri-results');
    const info = document.getElementById('santri-info');

    if (!input || !box) return;

    let timer = null;
    let controller = null;

    const render = (items) => {
        if (!items.length) {
            box.innerHTML = `<div class="px-4 py-3 text-sm text-slate-500">Santri tidak ditemukan</div>`;
        } else {
            box.innerHTML = items.map((s) => `
                <button type="button" class="santri-option flex w-full items-center gap-3 px-4 py-3 text-left
                    hover:bg-brand-50 transition-colors border-b border-slate-100 last:border-0"
                    data-id="${s.id}" data-nis="${s.nis}" data-nama="${s.nama}"
                    data-kamar="${s.kamar ?? ''}" data-jk="${s.jenis_kelamin}" data-foto="${s.foto ?? ''}">
                    <span class="grid place-items-center size-9 rounded-xl bg-brand-100 text-brand-700 font-bold text-xs shrink-0">
                        ${s.nama.split(' ').map((p) => p[0]).slice(0, 2).join('')}
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold text-slate-800 truncate">${s.nama}</span>
                        <span class="block text-xs text-slate-500">NIS ${s.nis}${s.kamar ? ' · ' + s.kamar : ''}</span>
                    </span>
                </button>
            `).join('');
        }
        box.classList.remove('hidden');
        renderIcons(box);
    };

    const fetchSantri = (q) => {
        controller?.abort();
        controller = new AbortController();
        fetch(`/api/santri/cari?q=${encodeURIComponent(q)}`, { signal: controller.signal })
            .then((r) => r.json())
            .then((res) => render(res.data || []))
            .catch(() => {});
    };

    input.addEventListener('focus', () => fetchSantri(input.value.trim()));
    input.addEventListener('input', () => {
        hidden.value = '';
        clearTimeout(timer);
        timer = setTimeout(() => fetchSantri(input.value.trim()), 250);
    });

    document.addEventListener('click', (e) => {
        const opt = e.target.closest('.santri-option');

        if (opt) {
            const { id, nis, nama, kamar, jk, foto } = opt.dataset;
            hidden.value = id;
            input.value = `${nis} - ${nama}`;
            box.classList.add('hidden');

            if (info) {
                info.classList.remove('hidden');
                info.innerHTML = `
                    <div class="flex items-center gap-4">
                        <div class="grid place-items-center size-14 rounded-2xl bg-brand-600 text-white font-bold text-lg shadow-md shadow-brand-600/30">
                            ${nama.split(' ').map((p) => p[0]).slice(0, 2).join('')}
                        </div>
                        <div>
                            <p class="font-bold text-slate-900 text-lg leading-tight">${nama}</p>
                            <p class="text-sm text-slate-600 mt-0.5">NIS: <span class="font-semibold">${nis}</span></p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                ${kamar ? 'Kamar: <span class="font-semibold">' + kamar + '</span> · ' : ''}
                                ${jk ? 'Jenis Kelamin: <span class="font-semibold">' + jk + '</span>' : ''}
                            </p>
                        </div>
                    </div>`;
                renderIcons(info);
            }
            return;
        }

        if (!e.target.closest('#santri-search') && !e.target.closest('#santri-results')) {
            box?.classList.add('hidden');
        }
    });
}

/* ------------------------------------------------------------------ */
/* PRATINJAU POIN PADA FORM ABSENSI                                     */
/* ------------------------------------------------------------------ */
function initPoinPreview() {
    const selects = document.querySelectorAll('[data-poin-select]');
    if (!selects.length) return;

    const totalEl = document.getElementById('poin-total');

    const update = () => {
        let total = 0;
        selects.forEach((sel) => {
            const opt = sel.selectedOptions[0];
            total += parseInt(opt?.dataset.poin ?? 0, 10);

            // warnai cell sesuai status
            const wrap = sel.closest('[data-cell]');
            if (wrap) {
                wrap.classList.remove('bg-emerald-50', 'bg-rose-50', 'bg-amber-50', 'bg-sky-50');
                const status = sel.value;
                if (status === 'Hadir') wrap.classList.add('bg-emerald-50');
                else if (status === 'Alfa') wrap.classList.add('bg-rose-50');
                else if (status === 'Terlambat' || status === 'Sakit') wrap.classList.add('bg-amber-50');
                else if (status === 'Izin') wrap.classList.add('bg-sky-50');
            }
        });

        if (totalEl) {
            totalEl.textContent = (total >= 0 ? '+' : '') + total;
            totalEl.classList.toggle('text-emerald-600', total >= 0);
            totalEl.classList.toggle('text-rose-600', total < 0);
            totalEl.classList.remove('animate-pop');
            void totalEl.offsetWidth;
            totalEl.classList.add('animate-pop');
        }
    };

    selects.forEach((sel) => sel.addEventListener('change', update));
    update();
}

/* ------------------------------------------------------------------ */
/* IMPORT MODAL (Santri Import Excel)                                  */
/* ------------------------------------------------------------------ */
function initImportModal() {
    const modal = document.getElementById('import-modal');
    if (!modal) return;

    const openBtns = document.querySelectorAll('[data-import-modal]');
    const closeBtns = modal.querySelectorAll('[data-import-close]');
    const backdrop = modal.querySelector('[data-import-close]'); // first one is backdrop

    const open = () => {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    };
    const close = () => {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    };

    openBtns.forEach((btn) => btn.addEventListener('click', open));
    closeBtns.forEach((btn) => btn.addEventListener('click', close));
    backdrop?.addEventListener('click', close);

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            close();
        }
    });
}

/* ------------------------------------------------------------------ */
/* PWA                                                                 */
/* ------------------------------------------------------------------ */
function initServiceWorker() {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        });
    }
}

/* ------------------------------------------------------------------ */
/* SESSION EXPIRATION WARNING & CSRF REFRESH                          */
/* ------------------------------------------------------------------ */
function initSessionExpiration() {
    const lifetime = 240 * 60 * 1000; // 240 minutes in ms (from .env SESSION_LIFETIME)
    const warningTime = 5 * 60 * 1000; // 5 minutes before expiry
    let warningShown = false;

    const checkSession = () => {
        const lastActivity = parseInt(localStorage.getItem('lastActivity') || '0', 10);
        const now = Date.now();

        if (!lastActivity) {
            localStorage.setItem('lastActivity', now.toString());
            return;
        }

        const timeLeft = lifetime - (now - lastActivity);

        if (timeLeft <= 0) {
            // Session expired, reload page to get new CSRF token
            window.location.reload();
            return;
        }

        if (timeLeft <= warningTime && !warningShown) {
            warningShown = true;
            if (confirm('Sesi Anda akan berakhir dalam 5 menit. Klik OK untuk memperpanjang sesi.')) {
                extendSession();
            }
        }
    };

    const extendSession = async () => {
        try {
            await fetch(window.location.href, {
                method: 'HEAD',
                credentials: 'same-origin',
            });
            localStorage.setItem('lastActivity', Date.now().toString());
            warningShown = false;
        } catch (e) {
            console.warn('Failed to extend session:', e);
        }
    };

    // Update last activity on user interaction
    ['click', 'keypress', 'mousemove', 'scroll', 'touchstart'].forEach(event => {
        document.addEventListener(event, () => {
            localStorage.setItem('lastActivity', Date.now().toString());
            warningShown = false;
        }, { passive: true });
    });

    // Check every minute
    setInterval(checkSession, 60 * 1000);
    checkSession();

    // Auto-refresh CSRF token every 30 minutes
    setInterval(async () => {
        try {
            const response = await fetch('/sanctum/csrf-cookie', {
                credentials: 'same-origin',
            });
            if (response.ok) {
                console.log('CSRF token refreshed');
            }
        } catch (e) {
            console.warn('CSRF refresh failed:', e);
        }
    }, 30 * 60 * 1000);
}
