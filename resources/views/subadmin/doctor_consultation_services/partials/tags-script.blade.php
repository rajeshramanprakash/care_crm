<script>
(function () {
  var groupEl = document.getElementById('dcs_main_tags_group');
  var listEl = document.getElementById('dcs_tags_list');
  var inputEl = document.getElementById('dcs_tag_input');
  var hiddenEl = document.getElementById('specialization_options_text');
  var formEl = document.querySelector('.dcs-form');
  if (!listEl || !inputEl || !hiddenEl) return;

  var tags = [];
  var mainTagsEnabled = true;

  function normalizeTag(s) {
    return (s || '').replace(/\s+/g, ' ').trim();
  }

  function syncHidden() {
    hiddenEl.value = mainTagsEnabled ? tags.join('\n') : '';
  }

  function render() {
    listEl.innerHTML = '';
    if (!mainTagsEnabled) {
      syncHidden();
      return;
    }
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
      listEl.appendChild(chip);
    });
    syncHidden();
  }

  function addTag(raw) {
    if (!mainTagsEnabled) return;
    var t = normalizeTag(raw);
    if (!t || t.length > 255) return;
    var key = t.toLowerCase();
    if (tags.some(function (x) { return x.toLowerCase() === key; })) return;
    tags.push(t);
    render();
  }

  function loadFromHidden() {
    tags = [];
    (hiddenEl.value || '').split(/\r?\n/).forEach(function (line) {
      addTag(line);
    });
    tags = tags.slice();
    render();
  }

  window.dcsSetMainTagsEnabled = function (enabled) {
    mainTagsEnabled = !!enabled;
    if (groupEl) {
      groupEl.classList.toggle('dcs-tags-disabled', !mainTagsEnabled);
    }
    inputEl.disabled = !mainTagsEnabled;
    inputEl.setAttribute('aria-disabled', mainTagsEnabled ? 'false' : 'true');
    if (!mainTagsEnabled) {
      inputEl.value = '';
      tags = [];
      render();
    }
    var req = document.querySelector('.dcs-main-tags-req');
    if (req) req.classList.add('d-none');
    var hintOn = document.querySelector('.dcs-main-tags-hint-active');
    var hintOff = document.querySelector('.dcs-main-tags-hint-disabled');
    if (hintOn) hintOn.classList.toggle('d-none', !mainTagsEnabled);
    if (hintOff) hintOff.classList.toggle('d-none', mainTagsEnabled);
  };

  inputEl.addEventListener('keydown', function (e) {
    if (!mainTagsEnabled) return;
    if (e.key === 'Enter' || e.key === ',') {
      e.preventDefault();
      addTag(inputEl.value);
      inputEl.value = '';
      return;
    }
    if (e.key === 'Backspace' && inputEl.value === '' && tags.length) {
      tags.pop();
      render();
    }
  });

  inputEl.addEventListener('blur', function () {
    if (!mainTagsEnabled) return;
    if (normalizeTag(inputEl.value)) {
      addTag(inputEl.value);
      inputEl.value = '';
    }
  });

  if (formEl) {
    formEl.addEventListener('submit', function () {
      if (!mainTagsEnabled) {
        syncHidden();
        return;
      }
      if (normalizeTag(inputEl.value)) {
        addTag(inputEl.value);
        inputEl.value = '';
      }
      syncHidden();
    });
  }

  loadFromHidden();
})();
</script>
