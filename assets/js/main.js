/**
 * =========================================================
 * READIWORK -- Unified Main JavaScript
 * =========================================================
 *
 * Single shared JS file for all public-facing pages.
 * Every feature gracefully degrades -- functions only run
 * when their required DOM elements exist on the page.
 *
 * Features:
 *  1. Navbar scroll detection
 *  2. Mobile menu toggle
 *  3. Dropdown menu (Quick Verify)
 *  4. Close dropdown on outside click
 *  5. Scroll progress bar
 *  6. Scroll reveal animations (IntersectionObserver)
 *  7. Counter animation
 *  8. Typing effect (hero section, AI-themed)
 *  9. Floating particles
 * 10. Tab switching
 * 11. Smooth scroll for anchor links
 * 12. 3D tilt effect on stat cards
 * 13. Service search & filter (services.php)
 * =========================================================
 */

(function () {
  'use strict';

  // --------------------------------------------------
  // Utility: safely query elements
  // --------------------------------------------------
  function $(selector) {
    return document.querySelector(selector);
  }

  function $$(selector) {
    return document.querySelectorAll(selector);
  }

  // --------------------------------------------------
  // Wait for DOM ready, then initialise everything
  // --------------------------------------------------
  document.addEventListener('DOMContentLoaded', function () {
    initNavbarScroll();
    initMobileMenu();
    initDropdown();
    initScrollProgress();
    initScrollReveal();
    initCounters();
    initTypingEffect();
    initParticles();
    initTabs();
    initSmoothScroll();
    initTiltEffect();
    initServiceSearch();
  });

  // ==========================================================
  // 1. NAVBAR SCROLL DETECTION
  //    Adds class "scrolled" to #rwNavbar when scrollY > 20
  // ==========================================================
  function initNavbarScroll() {
    var navbar = $('#rwNavbar');
    if (!navbar) return;

    function onScroll() {
      navbar.classList.toggle('scrolled', window.scrollY > 20);
    }

    window.addEventListener('scroll', onScroll, { passive: true });

    // Run once on load in case the page is already scrolled
    onScroll();
  }

  // ==========================================================
  // 2. MOBILE MENU TOGGLE
  //    Toggles "active" on #navToggle, "open" on #navLinks
  // ==========================================================
  function initMobileMenu() {
    var toggle = $('#navToggle');
    var links = $('#navLinks');
    if (!toggle || !links) return;

    toggle.addEventListener('click', function () {
      toggle.classList.toggle('active');
      links.classList.toggle('open');
    });

    // Close mobile menu when a nav link is clicked (better UX)
    var navLinks = links.querySelectorAll('.rw-nav-link');
    navLinks.forEach(function (link) {
      link.addEventListener('click', function () {
        if (links.classList.contains('open')) {
          toggle.classList.remove('active');
          links.classList.remove('open');
        }
      });
    });
  }

  // ==========================================================
  // 3 & 4. DROPDOWN MENU TOGGLE (Quick Verify)
  //         Toggle "open" on #navDropdown, close on outside click
  // ==========================================================
  function initDropdown() {
    var dropdown = $('#navDropdown');
    var dropdownToggle = $('#dropdownToggle');
    if (!dropdown || !dropdownToggle) return;

    dropdownToggle.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      dropdown.classList.toggle('open');
    });

    // Close when clicking anywhere outside the dropdown
    document.addEventListener('click', function (e) {
      if (!dropdown.contains(e.target)) {
        dropdown.classList.remove('open');
      }
    });

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        dropdown.classList.remove('open');
      }
    });
  }

  // ==========================================================
  // 5. SCROLL PROGRESS BAR
  //    Updates transform scaleX based on scroll position
  // ==========================================================
  function initScrollProgress() {
    var bar = $('#scrollProgress');
    if (!bar) return;

    function updateProgress() {
      var scrollableHeight =
        document.documentElement.scrollHeight - window.innerHeight;
      if (scrollableHeight <= 0) {
        bar.style.transform = 'scaleX(0)';
        return;
      }
      var progress = Math.min(window.scrollY / scrollableHeight, 1);
      bar.style.transform = 'scaleX(' + progress + ')';
    }

    window.addEventListener('scroll', updateProgress, { passive: true });

    // Initialise on load
    updateProgress();
  }

  // ==========================================================
  // 6. SCROLL REVEAL ANIMATIONS (IntersectionObserver)
  //    Adds "visible" class to .rw-reveal-trigger elements
  //    when they enter the viewport. Also handles:
  //    .rw-reveal, .rw-reveal-left, .rw-reveal-right,
  //    .rw-reveal-scale, .rw-stagger
  // ==========================================================
  function initScrollReveal() {
    var triggers = $$('.rw-reveal-trigger');
    if (!triggers.length) return;

    // Feature-detect IntersectionObserver
    if (!('IntersectionObserver' in window)) {
      // Fallback: make everything visible immediately
      triggers.forEach(function (el) {
        el.classList.add('visible');
      });
      return;
    }

    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            observer.unobserve(entry.target);
          }
        });
      },
      {
        threshold: 0.1,
        rootMargin: '0px 0px -60px 0px',
      }
    );

    triggers.forEach(function (el) {
      observer.observe(el);
    });
  }

  // ==========================================================
  // 7. COUNTER ANIMATION
  //    Animates .rw-counter elements from 0 to data-target
  // ==========================================================
  function initCounters() {
    var counters = $$('.rw-counter');
    if (!counters.length) return;

    var started = false;

    function animateCounters() {
      if (started) return;
      started = true;

      counters.forEach(function (counter) {
        var target = parseInt(counter.getAttribute('data-target'), 10);
        if (isNaN(target)) return;

        var duration = 2000;
        var frameDuration = 16;
        var totalFrames = Math.round(duration / frameDuration);
        var step = target / totalFrames;
        var current = 0;

        var timer = setInterval(function () {
          current += step;
          if (current >= target) {
            counter.textContent = target.toLocaleString();
            clearInterval(timer);
          } else {
            counter.textContent = Math.floor(current).toLocaleString();
          }
        }, frameDuration);
      });
    }

    // Use IntersectionObserver so counters animate when scrolled
    // into view; otherwise fall back to a timed delay.
    if ('IntersectionObserver' in window) {
      var counterObserver = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              animateCounters();
              counterObserver.disconnect();
            }
          });
        },
        { threshold: 0.3 }
      );

      counters.forEach(function (counter) {
        counterObserver.observe(counter);
      });
    } else {
      setTimeout(animateCounters, 1500);
    }
  }

  // ==========================================================
  // 8. TYPING EFFECT (Hero Section)
  //    Cycles through words on #heroTyping with type / delete.
  //    Includes AI-themed wording: "With AI."
  // ==========================================================
  function initTypingEffect() {
    var el = $('#heroTyping');
    if (!el) return;

    var words = ['Instantly.', 'Securely.', 'Accurately.', 'With AI.'];
    var wordIndex = 0;
    var charIndex = 0;
    var isDeleting = false;

    var TYPE_SPEED = 80;
    var DELETE_SPEED = 40;
    var PAUSE_AFTER_WORD = 2000;
    var PAUSE_BEFORE_NEXT = 400;
    var INITIAL_DELAY = 1200;

    function tick() {
      var currentWord = words[wordIndex];

      if (!isDeleting) {
        el.textContent = currentWord.substring(0, charIndex + 1);
        charIndex++;

        if (charIndex === currentWord.length) {
          setTimeout(function () {
            isDeleting = true;
            tick();
          }, PAUSE_AFTER_WORD);
          return;
        }

        setTimeout(tick, TYPE_SPEED);
      } else {
        el.textContent = currentWord.substring(0, charIndex - 1);
        charIndex--;

        if (charIndex === 0) {
          isDeleting = false;
          wordIndex = (wordIndex + 1) % words.length;
          setTimeout(tick, PAUSE_BEFORE_NEXT);
          return;
        }

        setTimeout(tick, DELETE_SPEED);
      }
    }

    setTimeout(tick, INITIAL_DELAY);
  }

  // ==========================================================
  // 9. FLOATING PARTICLES
  //    Generates particle divs inside .rw-particles container
  // ==========================================================
  function initParticles() {
    var container = $('#rwParticles');
    if (!container) return;

    var PARTICLE_COUNT = 20;

    for (var i = 0; i < PARTICLE_COUNT; i++) {
      var particle = document.createElement('div');
      particle.className = 'rw-particle';
      particle.style.left = (Math.random() * 100) + '%';

      var size = (Math.random() * 4 + 2) + 'px';
      particle.style.width = size;
      particle.style.height = size;

      particle.style.animationDuration = (Math.random() * 15 + 10) + 's';
      particle.style.animationDelay = (Math.random() * 10) + 's';
      particle.style.opacity = (Math.random() * 0.4 + 0.1).toString();

      container.appendChild(particle);
    }
  }

  // ==========================================================
  // 10. TAB SWITCHING
  //     .rw-tab elements with data-tab activate #panel-{tab}
  // ==========================================================
  function initTabs() {
    var tabs = $$('.rw-tab');
    if (!tabs.length) return;

    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        var targetTab = tab.getAttribute('data-tab');
        if (!targetTab) return;

        // Deactivate all tabs
        tabs.forEach(function (t) {
          t.classList.remove('active');
        });

        // Deactivate all panels
        $$('.rw-tab-panel').forEach(function (panel) {
          panel.classList.remove('active');
        });

        // Activate clicked tab
        tab.classList.add('active');

        // Activate corresponding panel
        var targetPanel = $('#panel-' + targetTab);
        if (targetPanel) {
          targetPanel.classList.add('active');
        }
      });
    });
  }

  // ==========================================================
  // 11. SMOOTH SCROLL FOR ANCHOR LINKS
  //     Scrolls smoothly to in-page #hash targets
  // ==========================================================
  function initSmoothScroll() {
    var anchors = $$('a[href^="#"]');
    if (!anchors.length) return;

    anchors.forEach(function (anchor) {
      anchor.addEventListener('click', function (e) {
        var href = anchor.getAttribute('href');

        // Skip if it is just "#" or empty
        if (!href || href === '#') return;

        var target;
        try {
          target = $(href);
        } catch (err) {
          // Invalid selector -- skip
          return;
        }

        if (target) {
          e.preventDefault();
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });

          // Close mobile menu if open
          var navLinks = $('#navLinks');
          var navToggle = $('#navToggle');
          if (navLinks && navLinks.classList.contains('open')) {
            navLinks.classList.remove('open');
            if (navToggle) navToggle.classList.remove('active');
          }
        }
      });
    });
  }

  // ==========================================================
  // 12. 3D TILT EFFECT ON STAT CARDS
  //     Perspective-based tilt on mousemove
  // ==========================================================
  function initTiltEffect() {
    var cards = $$('.rw-stat-card');
    if (!cards.length) return;

    // Skip on touch-only devices (no hover capability)
    if (window.matchMedia && window.matchMedia('(hover: none)').matches) {
      return;
    }

    cards.forEach(function (card) {
      card.addEventListener('mousemove', function (e) {
        var rect = card.getBoundingClientRect();
        var x = (e.clientX - rect.left) / rect.width - 0.5;
        var y = (e.clientY - rect.top) / rect.height - 0.5;

        card.style.transform =
          'perspective(600px)' +
          ' rotateY(' + (x * 8) + 'deg)' +
          ' rotateX(' + (-y * 8) + 'deg)' +
          ' translateY(-6px)' +
          ' scale(1.02)';
      });

      card.addEventListener('mouseleave', function () {
        card.style.transform = '';
      });
    });
  }

  // ==========================================================
  // 13. SERVICE SEARCH & FILTER (services.php page)
  //     Text search + category filter buttons for .service-item
  // ==========================================================
  function initServiceSearch() {
    var searchInput = $('#searchInput');
    var filterBtns = $$('.filter-btn');
    var serviceItems = $$('.service-item');
    var resultCountEl = $('#resultCount');
    var noResultsEl = $('#noResults');

    // Only run if the page has service items and a search input
    if (!searchInput || !serviceItems.length) return;

    var currentCategory = 'all';
    var currentSearch = '';

    function filterServices() {
      var visibleCount = 0;

      serviceItems.forEach(function (item) {
        var category = item.getAttribute('data-category') || '';
        var searchText =
          (item.getAttribute('data-search') || '').toLowerCase();
        var cardTitle = item.querySelector('h4');
        var titleText = cardTitle
          ? cardTitle.textContent.toLowerCase()
          : '';

        var matchesCategory =
          currentCategory === 'all' ||
          category.indexOf(currentCategory) !== -1;
        var matchesSearch =
          currentSearch === '' ||
          searchText.indexOf(currentSearch) !== -1 ||
          titleText.indexOf(currentSearch) !== -1;

        if (matchesCategory && matchesSearch) {
          item.style.display = '';
          visibleCount++;
        } else {
          item.style.display = 'none';
        }
      });

      if (resultCountEl) {
        resultCountEl.textContent = visibleCount;
      }
      if (noResultsEl) {
        noResultsEl.style.display =
          visibleCount === 0 ? 'block' : 'none';
      }
    }

    // Bind search input
    searchInput.addEventListener('input', function (e) {
      currentSearch = e.target.value.toLowerCase().trim();
      filterServices();
    });

    // Bind category filter buttons
    filterBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        filterBtns.forEach(function (b) {
          b.classList.remove('active');
        });
        btn.classList.add('active');
        currentCategory =
          btn.getAttribute('data-category') || 'all';
        filterServices();
      });
    });

    // Initial run to set counts
    filterServices();
  }
})();
