(() => {
  'use strict';

  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
  const money = value => new Intl.NumberFormat('vi-VN').format(value) + 'đ';

  const toast = message => {
    const element = $('[data-toast]');
    if (!element) return;
    element.textContent = message;
    element.classList.add('show');
    window.clearTimeout(toast.timer);
    toast.timer = window.setTimeout(() => element.classList.remove('show'), 2600);
  };

  $$('[data-toast-message]').forEach(button => {
    button.addEventListener('click', event => {
      if (button.tagName === 'A') event.preventDefault();
      toast(button.dataset.toastMessage);
    });
  });

  if ($('[data-welcome]')) toast('Đăng nhập thành công. Chào mừng bạn trở lại!');
  const serverFlash = $('[data-server-flash]');
  if (serverFlash) toast(serverFlash.textContent.trim());

  const menuButton = $('[data-menu-toggle]');
  const mobileNav = $('[data-mobile-nav]');
  menuButton?.addEventListener('click', () => mobileNav?.classList.toggle('open'));

  const profileButton = $('[data-profile-toggle]');
  const profilePopover = $('[data-profile-popover]');
  profileButton?.addEventListener('click', () => profilePopover?.classList.toggle('open'));
  document.addEventListener('click', event => {
    if (profilePopover && !event.target.closest('.profile-menu')) profilePopover.classList.remove('open');
  });

  const cinemaToggle = $('[data-cinema-toggle]');
  const cinemaPopover = $('[data-cinema-popover]');
  cinemaToggle?.addEventListener('click', event => {
    event.stopPropagation();
    const open = cinemaPopover?.classList.toggle('open');
    cinemaToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  $$('[data-cinema-name]').forEach(option => option.addEventListener('click', () => {
    const name = option.dataset.cinemaName || 'CineNeon';
    const selected = $('[data-selected-cinema]');
    if (selected) selected.textContent = name.replace(/^CineNeon\s*/, '');
    localStorage.setItem('cineneonCinema', JSON.stringify({ name, address: option.dataset.cinemaAddress || '' }));
    cinemaPopover?.classList.remove('open');
    cinemaToggle?.setAttribute('aria-expanded', 'false');
    toast(`Đã chọn ${name}`);
  }));
  document.addEventListener('click', event => {
    if (cinemaPopover && !event.target.closest('.cinema-picker, .cinema-popover')) {
      cinemaPopover.classList.remove('open');
      cinemaToggle?.setAttribute('aria-expanded', 'false');
    }
  });
  try {
    const savedCinema = JSON.parse(localStorage.getItem('cineneonCinema'));
    if (savedCinema?.name && $('[data-selected-cinema]')) $('[data-selected-cinema]').textContent = savedCinema.name.replace(/^CineNeon\s*/, '');
  } catch (_) {}

  const heroCard = $('[data-hero-card]');
  const heroMovies = window.CINENEON_MOVIES || [];
  if (heroCard && heroMovies.length > 1) {
    const heroTitle = $('[data-hero-title]');
    const heroDescription = $('[data-hero-description]');
    const heroMetaAge = $('[data-hero-age]');
    const heroDuration = $('[data-hero-duration]');
    const heroRating = $('[data-hero-rating]');
    const heroLink = $('[data-hero-link]');
    const heroDots = $$('[data-hero-dot]');
    let heroIndex = 0;
    const showHero = index => {
      const movie = heroMovies[index];
      heroCard.classList.add('is-changing');
      window.setTimeout(() => {
        heroCard.style.setProperty('--hero-image', `url("${movie.backdrop || movie.poster}")`);
        if (heroTitle) heroTitle.textContent = movie.title;
        if (heroDescription) heroDescription.textContent = movie.synopsis || 'Khám phá những suất chiếu nổi bật và đặt ghế yêu thích của bạn ngay hôm nay.';
        if (heroMetaAge) heroMetaAge.textContent = movie.age;
        if (heroDuration) heroDuration.textContent = `${movie.duration} phút`;
        if (heroRating) heroRating.textContent = movie.rating;
        if (heroLink) heroLink.href = `index.php?page=movie&movie=${movie.id}`;
        heroDots.forEach((dot, dotIndex) => dot.classList.toggle('active', dotIndex === index));
        heroCard.classList.remove('is-changing');
      }, 220);
    };
    window.setInterval(() => { heroIndex = (heroIndex + 1) % heroMovies.length; showHero(heroIndex); }, 6000);
    heroDots.forEach((dot, index) => dot.addEventListener('click', () => { heroIndex = index; showHero(heroIndex); }));
  }

  const searchDialog = $('[data-search-dialog]');
  const searchInput = $('[data-search-input]');
  const searchResults = $('[data-search-results]');
  const openSearch = () => {
    if (!searchDialog) return;
    searchDialog.classList.add('open');
    searchDialog.setAttribute('aria-hidden', 'false');
    window.setTimeout(() => searchInput?.focus(), 30);
    renderSearch('');
  };
  const closeSearch = () => {
    searchDialog?.classList.remove('open');
    searchDialog?.setAttribute('aria-hidden', 'true');
  };
  const renderSearch = query => {
    if (!searchResults) return;
    const normalized = query.trim().toLocaleLowerCase('vi');
    const items = (window.CINENEON_MOVIES || []).filter(item => !normalized || `${item.title} ${item.subtitle}`.toLocaleLowerCase('vi').includes(normalized)).slice(0, 5);
    searchResults.innerHTML = items.length
      ? items.map(item => `<a class="search-result" href="index.php?page=movie&movie=${item.id}"><img src="${item.poster}" alt=""><span><strong>${item.title}</strong><small>${item.subtitle}</small></span></a>`).join('')
      : '<div class="empty-state"><strong>Không tìm thấy phim phù hợp.</strong></div>';
  };
  $$('[data-search-open]').forEach(button => button.addEventListener('click', openSearch));
  $$('[data-search-close]').forEach(button => button.addEventListener('click', closeSearch));
  searchInput?.addEventListener('input', event => renderSearch(event.target.value));
  document.addEventListener('keydown', event => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
      event.preventDefault();
      openSearch();
    }
    if (event.key === 'Escape') closeSearch();
  });

  $$('[data-tabs], .date-tabs').forEach(tabList => {
    $$('button', tabList).forEach(button => button.addEventListener('click', () => {
      $$('button', tabList).forEach(item => item.classList.remove('active'));
      button.classList.add('active');
    }));
  });

  const movieGrid = $('[data-movie-grid]');
  if (movieGrid) {
    const state = { format: 'all', age: 'all', genre: 'all', sort: 'hot' };
    const updateMovies = () => {
      const cards = $$('.movie-card', movieGrid);
      cards.forEach(card => {
        const matchesFormat = state.format === 'all' || card.dataset.format.includes(state.format);
        const matchesAge = state.age === 'all' || card.dataset.age === state.age;
        const matchesGenre = state.genre === 'all' || card.dataset.genre.includes(state.genre);
        card.hidden = !(matchesFormat && matchesAge && matchesGenre);
      });
      cards.sort((a, b) => Number(b.dataset[state.sort]) - Number(a.dataset[state.sort])).forEach(card => movieGrid.appendChild(card));
      const visible = cards.filter(card => !card.hidden).length;
      $('[data-empty-state]')?.toggleAttribute('hidden', visible > 0);
    };
    $$('[data-filter-group]').forEach(group => {
      $$('button', group).forEach(button => button.addEventListener('click', () => {
        $$('button', group).forEach(item => item.classList.remove('active'));
        button.classList.add('active');
        const key = group.dataset.filterGroup;
        if (key === 'format' || key === 'age') state[key] = button.dataset.value || 'all';
        updateMovies();
      }));
    });
    $('[data-genre-filter]')?.addEventListener('change', event => { state.genre = event.target.value; updateMovies(); });
    $('[data-sort-filter]')?.addEventListener('change', event => { state.sort = event.target.value; updateMovies(); });
  }

  const quickBooking = $('[data-quick-booking]');
  const quickButton = $('[data-quick-booking-submit]');
  quickButton?.addEventListener('click', event => {
    event.preventDefault();
    const movieId = $('[name="quick_movie"]', quickBooking)?.value || '1';
    window.location.href = `index.php?page=movie&movie=${encodeURIComponent(movieId)}#showtimes`;
  });

  const seatMap = $('[data-seat-map]');
  if (seatMap) {
    const selected = new Map();
    const chips = $('[data-selected-chips]');
    const subtotal = $('[data-seat-subtotal]');
    const total = $('[data-seat-total]');
    const continueButton = $('[data-continue-booking]');
    const updateSeats = () => {
      const sum = [...selected.values()].reduce((result, price) => result + price, 0);
      chips.innerHTML = selected.size
        ? [...selected.keys()].map(seat => `<span>${seat}</span>`).join('')
        : '<span class="muted">Chưa chọn ghế</span>';
      subtotal.textContent = money(sum);
      total.innerHTML = `${new Intl.NumberFormat('vi-VN').format(sum)}<small>VNĐ</small>`;
      continueButton.classList.toggle('disabled', selected.size === 0);
      localStorage.setItem('cineneonBooking', JSON.stringify({ seats: [...selected.keys()], total: sum }));
    };
    $$('.seat:not(.sold)', seatMap).forEach(button => button.addEventListener('click', () => {
      const seat = button.dataset.seat;
      if (selected.has(seat)) {
        selected.delete(seat);
        button.classList.remove('selected');
      } else {
        if (selected.size >= 8) return toast('Bạn có thể chọn tối đa 8 ghế mỗi đơn.');
        selected.set(seat, Number(button.dataset.price));
        button.classList.add('selected');
      }
      updateSeats();
    }));
    $('[data-clear-seats]')?.addEventListener('click', () => {
      selected.clear();
      $$('.seat.selected', seatMap).forEach(seat => seat.classList.remove('selected'));
      updateSeats();
    });
    updateSeats();

    const countdown = $('[data-countdown]');
    let seconds = 300;
    const timer = window.setInterval(() => {
      seconds -= 1;
      if (!countdown) return window.clearInterval(timer);
      countdown.textContent = `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
      if (seconds <= 0) {
        window.clearInterval(timer);
        toast('Thời gian giữ ghế đã hết. Vui lòng chọn lại.');
      }
    }, 1000);

    const refreshSoldSeats = async () => {
      try {
        const url = new URL(window.location.href);
        url.searchParams.set('seat_status', '1');
        const response = await fetch(url, { headers: { Accept: 'application/json' }, cache: 'no-store' });
        if (!response.ok) return;
        const data = await response.json();
        (data.sold || []).forEach(seatCode => {
          const button = seatMap.querySelector(`[data-seat="${CSS.escape(seatCode)}"]`);
          if (!button) return;
          selected.delete(seatCode);
          button.classList.remove('selected');
          button.classList.add('sold');
          button.disabled = true;
          button.setAttribute('aria-label', `Ghế ${seatCode} đã bán`);
        });
        updateSeats();
      } catch (_) {}
    };
    window.setInterval(refreshSoldSeats, 10000);
  }

  const booking = (() => {
    try { return JSON.parse(localStorage.getItem('cineneonBooking')) || null; }
    catch (_) { return null; }
  })();
  if (booking?.seats?.length) {
    $('[data-checkout-seats]') && ($('[data-checkout-seats]').textContent = booking.seats.join(', '));
    const ticketSeats = $('[data-ticket-seats]');
    if (ticketSeats && ticketSeats.dataset.serverTicket !== '1') ticketSeats.textContent = booking.seats.join(' · ');
    $$('[data-order-subtotal], [data-order-total], [data-checkout-total]').forEach(element => element.textContent = money(booking.total));
  }
  if ($('[data-ticket-seats]')?.dataset.serverTicket === '1') localStorage.removeItem('cineneonBooking');

  $$('.payment-option').forEach(option => option.addEventListener('click', () => {
    $$('.payment-option').forEach(item => item.classList.remove('active'));
    option.classList.add('active');
    const cardFields = $('[data-card-fields]');
    if (cardFields) cardFields.hidden = option.querySelector('input')?.value !== 'card';
  }));
  $('[data-checkout-form]')?.addEventListener('submit', event => {
    const form = event.currentTarget;
    const savedBooking = (() => { try { return JSON.parse(localStorage.getItem('cineneonBooking')) || null; } catch (_) { return null; } })();
    const seatsField = $('[data-seats-json]', form);
    if (seatsField) seatsField.value = JSON.stringify(savedBooking?.seats || []);
    const button = $('.checkout-submit', event.currentTarget);
    button.innerHTML = '<span class="material-symbols-rounded">progress_activity</span>Đang xử lý giao dịch...';
    button.disabled = true;
    if (form.dataset.databaseOnline === '1') return;
    event.preventDefault();
    window.setTimeout(() => window.location.href = 'index.php?page=ticket&movie=1', 900);
  });

  $$('[data-password-toggle]').forEach(button => button.addEventListener('click', () => {
    const input = button.closest('.password-wrap')?.querySelector('input');
    if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
    $('.material-symbols-rounded', button).textContent = input.type === 'password' ? 'visibility' : 'visibility_off';
  }));

  const adminNav = $('[data-admin-nav]');
  const switchAdminView = name => {
    $$('[data-admin-view]').forEach(button => button.classList.toggle('active', button.dataset.adminView === name));
    $$('[data-admin-panel]').forEach(panel => panel.classList.toggle('active', panel.dataset.adminPanel === name));
    $('[data-admin-sidebar]')?.classList.remove('open');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };
  $$('[data-admin-view]', adminNav || document).forEach(button => button.addEventListener('click', () => switchAdminView(button.dataset.adminView)));
  $$('[data-admin-jump]').forEach(link => link.addEventListener('click', event => { event.preventDefault(); switchAdminView(link.dataset.adminJump); }));
  $('[data-admin-menu]')?.addEventListener('click', () => $('[data-admin-sidebar]')?.classList.toggle('open'));

  $$('[data-modal-open]').forEach(button => button.addEventListener('click', () => {
    const modal = document.getElementById(button.dataset.modalOpen);
    const form = $('.modal-card', modal || document);
    if (form && modal?.id === 'movie-modal') {
      form.reset();
      $('[data-movie-id]', form).value = '';
      if (button.dataset.movie) {
        const movie = JSON.parse(button.dataset.movie);
        const fields = {
          movie_id: movie.id, title: movie.title, original_title: movie.subtitle, duration: movie.duration,
          genres: movie.genre, release_date: movie.release_date || '', age_rating: movie.age,
          status: movie.status || 'now_showing', language: movie.language, rating: movie.rating,
          hot_score: movie.hot, poster_url: movie.poster, backdrop_url: movie.backdrop, synopsis: movie.synopsis || ''
        };
        Object.entries(fields).forEach(([name, value]) => {
          const field = form.elements.namedItem(name);
          if (field) field.value = value;
        });
      }
    }
    modal?.classList.add('open');
    modal?.setAttribute('aria-hidden', 'false');
  }));
  $$('[data-modal-close]').forEach(button => button.addEventListener('click', () => {
    const modal = button.closest('.modal');
    modal?.classList.remove('open');
    modal?.setAttribute('aria-hidden', 'true');
  }));
  $$('.modal-card').forEach(form => form.addEventListener('submit', event => {
    if (form.querySelector('[name="_action"]')) return;
    event.preventDefault();
    form.closest('.modal')?.classList.remove('open');
    toast('Đã lưu thay đổi trong bản giao diện mẫu.');
  }));
  $$('[data-archive-form]').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm('Bạn muốn xóa phim này khỏi website? Đây là xóa mềm nên dữ liệu lịch sử vẫn được giữ trong MySQL.')) event.preventDefault();
  }));
  $$('[data-confirm-delete]').forEach(button => button.addEventListener('click', () => {
    if (window.confirm('Bạn muốn ẩn phim này khỏi hệ thống?')) {
      button.closest('tr')?.remove();
      toast('Đã ẩn phim. Dữ liệu có thể khôi phục.');
    }
  }));
  $('[data-table-search]')?.addEventListener('input', event => {
    const query = event.target.value.toLocaleLowerCase('vi');
    $$('[data-table-row]').forEach(row => row.hidden = !row.textContent.toLocaleLowerCase('vi').includes(query));
  });
  const initialAdminView = $('[data-initial-admin-view]')?.dataset.initialAdminView;
  if (initialAdminView && $('[data-admin-panel="' + initialAdminView + '"]')) switchAdminView(initialAdminView);
})();
