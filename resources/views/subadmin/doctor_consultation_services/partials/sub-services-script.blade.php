<script>
(function () {
  var listEl = document.getElementById('dcs_sub_services_list');
  var tplEl = document.getElementById('dcs_sub_service_row_tpl');
  var addBtn = document.getElementById('dcs_add_sub_service');
  var emptyHint = document.getElementById('dcs_sub_services_empty');
  var formEl = document.querySelector('.dcs-form');
  if (!listEl || !tplEl || !addBtn) return;

  var nextIndex = 0;

  function syncMainServiceTags() {
    var count = listEl.querySelectorAll('.dcs-sub-service-card:not(.dcs-sub-deleted)').length;
    if (typeof window.dcsSetMainTagsEnabled === 'function') {
      window.dcsSetMainTagsEnabled(count === 0);
    }
  }

  function renumberCards() {
    var cards = listEl.querySelectorAll('.dcs-sub-service-card:not(.dcs-sub-deleted)');
    cards.forEach(function (card, i) {
      var num = card.querySelector('.dcs-sub-num');
      if (num) num.textContent = String(i + 1);
    });
    if (emptyHint) {
      emptyHint.classList.toggle('d-none', cards.length > 0);
    }
    syncMainServiceTags();
  }

  function initTagsOnCard(card) {
    if (card.getAttribute('data-tags-ready') === '1') return;

    var listTags = card.querySelector('.dcs-tags-list');
    var inputTags = card.querySelector('.dcs-sub-tags-input');
    var hiddenTags = card.querySelector('.dcs-sub-tags-hidden');
    if (!listTags || !inputTags || !hiddenTags) return;

    var tags = [];

    function normalizeTag(s) {
      return (s || '').replace(/\s+/g, ' ').trim();
    }

    function syncHidden() {
      hiddenTags.value = tags.join('\n');
    }

    function render() {
      listTags.innerHTML = '';
      tags.forEach(function (tag, idx) {
        var chip = document.createElement('span');
        chip.className = 'dcs-tag-chip';
        var label = document.createElement('span');
        label.textContent = tag;
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.setAttribute('aria-label', 'Remove ' + tag);
        btn.innerHTML = '&times;';
        btn.addEventListener('click', function () {
          tags.splice(idx, 1);
          render();
        });
        chip.appendChild(label);
        chip.appendChild(btn);
        listTags.appendChild(chip);
      });
      syncHidden();
    }

    function addTag(raw) {
      var t = normalizeTag(raw);
      if (!t || t.length > 255) return;
      var key = t.toLowerCase();
      if (tags.some(function (x) { return x.toLowerCase() === key; })) return;
      tags.push(t);
      render();
    }

    (hiddenTags.value || '').split(/\r?\n/).forEach(function (line) {
      addTag(line);
    });
    tags = tags.slice();
    render();

    inputTags.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        addTag(inputTags.value);
        inputTags.value = '';
      }
      if (e.key === 'Backspace' && inputTags.value === '' && tags.length) {
        tags.pop();
        render();
      }
    });

    inputTags.addEventListener('blur', function () {
      if (normalizeTag(inputTags.value)) {
        addTag(inputTags.value);
        inputTags.value = '';
      }
    });

    card._dcsFlushSubTags = function () {
      if (normalizeTag(inputTags.value)) {
        addTag(inputTags.value);
        inputTags.value = '';
      }
      syncHidden();
      return true;
    };

    card.setAttribute('data-tags-ready', '1');
  }

  function wireCard(card) {
    initTagsOnCard(card);

    var removeBtn = card.querySelector('.dcs-sub-remove');
    if (removeBtn && !removeBtn.getAttribute('data-wired')) {
      removeBtn.setAttribute('data-wired', '1');
      removeBtn.addEventListener('click', function () {
        var idInput = card.querySelector('input[name$="[id]"]');
        var delFlag = card.querySelector('.dcs-sub-delete-flag');
        if (idInput && delFlag) {
          delFlag.value = '1';
          card.classList.add('dcs-sub-deleted');
          card.style.display = 'none';
        } else {
          card.remove();
        }
        renumberCards();
      });
    }
  }

  function addCard() {
    var html = tplEl.innerHTML.replace(/__INDEX__/g, String(nextIndex));
    nextIndex += 1;
    var wrap = document.createElement('div');
    wrap.innerHTML = html.trim();
    var card = wrap.firstElementChild;
    if (!card) return;
    listEl.appendChild(card);
    wireCard(card);
    renumberCards();
    var nameInput = card.querySelector('.dcs-sub-name');
    if (nameInput) nameInput.focus();
  }

  listEl.querySelectorAll('.dcs-sub-service-card').forEach(function (card) {
    var idxMatch = card.getAttribute('data-sub-index');
    var n = parseInt(idxMatch, 10);
    if (!isNaN(n) && n >= nextIndex) nextIndex = n + 1;
    wireCard(card);
  });
  renumberCards();

  addBtn.addEventListener('click', addCard);

  if (formEl) {
    formEl.addEventListener('submit', function () {
      var namedSubs = 0;
      listEl.querySelectorAll('.dcs-sub-service-card:not(.dcs-sub-deleted)').forEach(function (card) {
        var n = ((card.querySelector('.dcs-sub-name') || {}).value || '').trim();
        if (n) namedSubs++;
      });
      if (namedSubs === 0 && typeof window.dcsSetMainTagsEnabled === 'function') {
        window.dcsSetMainTagsEnabled(true);
      }

      listEl.querySelectorAll('.dcs-sub-service-card:not(.dcs-sub-deleted)').forEach(function (card) {
        var name = (card.querySelector('.dcs-sub-name') || {}).value || '';
        name = name.trim();
        if (!name) return;
        if (typeof card._dcsFlushSubTags === 'function') {
          card._dcsFlushSubTags();
        }
      });
    });
  }
})();
</script>
