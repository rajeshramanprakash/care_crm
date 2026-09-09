(() => {
  const cfg =
    (window.__salesFutureProspectReminder ||
      window.__futureProspectReminder ||
      {}) ?? {};

  const checkUrl = cfg.fpReminderCheckUrl;
  const enabled =
    typeof window !== 'undefined' &&
    window.chatRealtimeConfig &&
    window.chatRealtimeConfig.futureProspectReminders;

  if (!enabled || !checkUrl) return;

  const $ = (id) => document.getElementById(id);

  /** @type {ReturnType<typeof setInterval> | null} */
  let fpLeadStatusPoll = null;

  const clearFpLeadStatusPoll = () => {
    if (fpLeadStatusPoll) {
      clearInterval(fpLeadStatusPoll);
      fpLeadStatusPoll = null;
    }
  };

  const DISMISS_PREFIX = 'carecrm_fp_reminder_dismissed:';
  const LAST_DUE_KEY = 'carecrm_fp_reminder_last_due';

  const reminderKeyFromPayload = (payload) =>
    `${payload?.lead_id || ''}:${payload?.reminder_due_at || ''}:${payload?.reminder_type || ''}`;

  const isReminderDismissed = (key) => {
    if (!key) return false;
    try {
      if (localStorage.getItem(DISMISS_PREFIX + key) === '1') return true;
      // One-time read of legacy session dismiss (same tab) then migrate.
      if (sessionStorage.getItem(DISMISS_PREFIX + key) === '1') {
        localStorage.setItem(DISMISS_PREFIX + key, '1');
        sessionStorage.removeItem(DISMISS_PREFIX + key);
        return true;
      }
    } catch {
      return false;
    }
    return false;
  };

  const markReminderDismissed = (key) => {
    if (!key) return;
    try {
      localStorage.setItem(DISMISS_PREFIX + key, '1');
      sessionStorage.removeItem(DISMISS_PREFIX + key);
      localStorage.removeItem(LAST_DUE_KEY);
    } catch {
      // private mode / quota
    }
  };

  const normCallStatus = (s) => String(s || '').trim().toLowerCase();

  const isTerminalDialStatus = (s) => {
    const v = normCallStatus(s);
    return (
      v === 'answered' ||
      v === 'busy' ||
      v === 'no answer' ||
      v === 'noanswer' ||
      v === 'failed' ||
      v === 'cancelled' ||
      v === 'canceled'
    );
  };

  const dialStatusUserMessage = (s) => {
    const v = normCallStatus(s);
    if (v === 'answered') {
      return 'Call connected. Finish your conversation with the customer, then press Close.';
    }
    if (v === 'busy') return 'Line busy. You can press Close when you are done.';
    if (v === 'no answer' || v === 'noanswer') return 'No answer / missed. You can press Close when you are done.';
    if (v === 'failed' || v === 'cancelled' || v === 'canceled') return 'Call could not be completed. You can press Close when you are done.';
    if (v) return `Call status: ${v}. You can press Close when you are done.`;
    return '';
  };

  const setText = (id, value) => {
    const el = $(id);
    if (!el) return;
    el.textContent = value == null || value === '' ? '-' : String(value);
  };

  const toggleRow = (id, show) => {
    const el = $(id);
    if (!el) return;
    el.classList.toggle('d-none', !show);
  };

  const MODAL_OPTIONS = { backdrop: 'static', keyboard: false };

  const modalRoot = $('futureProspectReminderModal');
  if (modalRoot && !modalRoot.dataset.fpReminderCleanupBound) {
    modalRoot.dataset.fpReminderCleanupBound = '1';
    modalRoot.addEventListener('hidden.bs.modal', () => {
      clearFpLeadStatusPoll();
      const cb = $('fpReminderCallBtn');
      const lbl = $('fpReminderCallBtnLabel');
      if (cb) cb.disabled = false;
      if (lbl) lbl.textContent = 'Callback';
    });
  }

  const openReminderModal = (payload) => {
    const modalEl = $('futureProspectReminderModal');
    if (!modalEl || !window.bootstrap || !window.bootstrap.Modal) return;

    const reminderKey = reminderKeyFromPayload(payload);
    // User closed this reminder (stored in localStorage) — do not reopen on reload/interval/Echo until due changes (new key).
    if (reminderKey && isReminderDismissed(reminderKey)) {
      return;
    }

    // Persist the last seen due payload so a full page reload can restore the modal
    // immediately, before the next network check, until the user closes or the call completes.
    try {
      localStorage.setItem(LAST_DUE_KEY, JSON.stringify(payload || {}));
    } catch {
      // ignore quota / private mode
    }

    // Short debounce only: same payload from Echo + HTTP in the same moment; do not block after refresh.
    const dedupeKey = reminderKey;
    const nowMs = Date.now();
    const last = window.__fpReminderDedupe || { key: '', at: 0 };
    if (dedupeKey && last.key === dedupeKey && nowMs - last.at < 5000) return;
    window.__fpReminderDedupe = { key: dedupeKey, at: nowMs };

    clearFpLeadStatusPoll();

    const isFollowUp = String(payload?.reminder_type || '') === 'follow_up';
    const title = isFollowUp ? 'Follow-up — callback due' : 'Future contact — callback due';
    const subtitle = isFollowUp
      ? 'This lead is assigned to you. The follow-up time has been reached.'
      : 'This lead is assigned to you. The scheduled contact time has been reached.';

    setText('futureProspectReminderTitle', title);
    setText('futureProspectReminderSubtitle', subtitle);

    setText('fpReminderWhen', payload?.reminder_due_display || payload?.future_prospect_date_display || payload?.follow_up_date_display || '-');
    setText('fpReminderLeadCode', payload?.lead_code || payload?.lead_id || '-');
    setText('fpReminderContact', payload?.contact_no || '-');
    setText('fpReminderCustomer', payload?.customer_name || '-');
    setText('fpReminderPatient', payload?.patient_name || '-');
    setText('fpReminderGender', payload?.patient_gender || '-');
    setText('fpReminderAge', payload?.age != null ? payload.age : '-');
    setText('fpReminderLocation', payload?.location || '-');
    setText('fpReminderLeadSource', payload?.lead_source || '-');
    setText('fpReminderQuery', payload?.query || '-');
    setText('fpReminderStage', payload?.stage || '-');
    setText('fpReminderStatus', payload?.status || '-');

    const queryRemarks = payload?.query_remarks || '';
    toggleRow('fpReminderQueryRemarksRow', !!String(queryRemarks).trim());
    setText('fpReminderQueryRemarks', queryRemarks);

    const statusEl = $('fpReminderCallStatus');
    if (statusEl) statusEl.textContent = '';

    // Wire buttons
    const closeBtn = $('fpReminderCloseBtn');
    const callBtn = $('fpReminderCallBtn');
    const callBtnLabel = $('fpReminderCallBtnLabel');

    if (callBtnLabel) callBtnLabel.textContent = 'Callback';

    if (closeBtn) {
      closeBtn.onclick = () => {
        markReminderDismissed(reminderKey);
        clearFpLeadStatusPoll();
        if (callBtn) callBtn.disabled = false;
        if (callBtnLabel) callBtnLabel.textContent = 'Callback';
        const inst = window.bootstrap.Modal.getOrCreateInstance(modalEl, MODAL_OPTIONS);
        inst.hide();
      };
    }

    if (callBtn) {
      callBtn.disabled = false;
      callBtn.onclick = async () => {
        const number = String(payload?.contact_no || '').trim();
        if (!number) return;
        const leadId = payload?.lead_id;

        clearFpLeadStatusPoll();
        callBtn.disabled = true;
        if (callBtnLabel) callBtnLabel.textContent = 'Calling…';
        if (statusEl) statusEl.textContent = 'Starting outbound call…';

        const outboundUrl = `${cfg.outboundBase || '/call-outbound'}/${encodeURIComponent(number)}`;

        const fetchLeadJson = async () => {
          if (!leadId) return null;
          const r = await fetch(`/sales/leads/${leadId}/edit`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
          });
          if (!r.ok) return null;
          return safeJson(r);
        };

        const finishCallUi = () => {
          if (callBtnLabel) callBtnLabel.textContent = 'Callback';
          callBtn.disabled = false;
        };

        try {
          const res = await fetch(outboundUrl, {
            method: 'GET',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
          });
          const body = await safeJson(res);
          const ok = res.ok && body && body.success === true;
          if (!ok) {
            const msg = (body && body.message) || 'Failed to start call';
            if (statusEl) statusEl.textContent = msg;
            if (window.toastr && window.toastr.error) window.toastr.error(msg);
            finishCallUi();
            return;
          }

          if (window.toastr && window.toastr.success) {
            window.toastr.success(body.message || 'Call initiated');
          }

          if (statusEl) {
            statusEl.textContent =
              'Call started on your phone. This dialog stays open until CRM shows ring/answer or a final dial status — it will not close by itself. Use Close when you are completely done.';
          }

          if (!leadId) {
            if (statusEl) {
              statusEl.textContent +=
                ' (Lead id missing — cannot check call status automatically.)';
            }
            finishCallUi();
            return;
          }

          let postStartSnapshot = normCallStatus(payload?.last_call_status);
          const firstLead = await fetchLeadJson();
          if (firstLead) postStartSnapshot = normCallStatus(firstLead.last_call_status);

          let attempts = 0;
          const maxAttempts = 120;

          const pollOnce = async () => {
            attempts += 1;
            const lead = await fetchLeadJson();
            if (!lead) {
              if (attempts >= maxAttempts) {
                clearFpLeadStatusPoll();
                if (statusEl) statusEl.textContent = 'Could not read lead status. Press Close when done.';
                finishCallUi();
              }
              return;
            }
            const st = normCallStatus(lead.last_call_status);
            const changed = st !== postStartSnapshot;
            if (changed && isTerminalDialStatus(st)) {
              clearFpLeadStatusPoll();
              if (statusEl) statusEl.textContent = dialStatusUserMessage(st);
              // Call finished with a terminal dial status – treat like explicit dismissal
              // for future auto-pop behaviour (no more auto-open until next scheduled time).
              markReminderDismissed(reminderKey);
              finishCallUi();
              return;
            }
            if (attempts >= maxAttempts) {
              clearFpLeadStatusPoll();
              if (statusEl) {
                statusEl.textContent =
                  'No new dial result appeared in CRM yet (ring may still be in progress). When your call is fully finished, press Close.';
              }
              finishCallUi();
            } else if (statusEl && attempts === 2) {
              statusEl.textContent =
                'Waiting for this call: ringing / answered / missed — CRM updates every few seconds. This window will not auto-close.';
            }
          };

          await pollOnce();
          fpLeadStatusPoll = setInterval(pollOnce, 5000);
        } catch (e) {
          if (statusEl) statusEl.textContent = 'Could not start call. Try again.';
          if (window.toastr && window.toastr.error) window.toastr.error('Could not start call');
          finishCallUi();
        }
      };
    }

    const instance = window.bootstrap.Modal.getOrCreateInstance(modalEl, MODAL_OPTIONS);
    instance.show();
  };

  const schedule = {
    timeoutId: null,
    lastNextDueTs: null
  };

  const clearExactTimer = () => {
    if (schedule.timeoutId) {
      clearTimeout(schedule.timeoutId);
      schedule.timeoutId = null;
    }
  };

  const setExactTimerFromServer = (serverNowTs, nextDueTs) => {
    clearExactTimer();
    if (!serverNowTs || !nextDueTs) return;

    // Avoid re-scheduling the same timestamp repeatedly.
    if (schedule.lastNextDueTs === nextDueTs) return;
    schedule.lastNextDueTs = nextDueTs;

    const nowMs = Number(serverNowTs) * 1000;
    const dueMs = Number(nextDueTs) * 1000;
    const delay = dueMs - nowMs;

    // If due is already here, run immediately.
    const safeDelay = !Number.isFinite(delay) ? 0 : Math.max(0, Math.min(delay, 24 * 60 * 60 * 1000));

    schedule.timeoutId = setTimeout(() => {
      // At exact due time, run a check and open modal from response even without websockets.
      runOnce();
    }, safeDelay);
  };

  const attachRealtimeListener = () => {
    try {
      if (!cfg.userId || !window.ChatRealtime || typeof window.ChatRealtime.getEcho !== 'function') {
        return;
      }
      const echo = window.ChatRealtime.getEcho();
      if (!echo) return;

      // Channel is per-user: PrivateChannel('App.Models.User.{id}')
      echo.private(`App.Models.User.${cfg.userId}`).listen('.future.prospect.reminder', (payload) => {
        openReminderModal(payload);
      });
    } catch {
      // Silent: reminders should never break CRM UI.
    }
  };

  const safeJson = async (res) => {
    try {
      return await res.json();
    } catch {
      return null;
    }
  };

  const restoreFromCache = () => {
    try {
      const raw = localStorage.getItem(LAST_DUE_KEY);
      if (!raw) return;
      const payload = JSON.parse(raw);
      const key = reminderKeyFromPayload(payload);
      if (key && isReminderDismissed(key)) {
        return;
      }
      if (payload && typeof payload === 'object') {
        openReminderModal(payload);
      }
    } catch {
      // ignore
    }
  };

  const runOnce = async () => {
    try {
      const csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
      // This route is expected to be side-effecting (dispatch reminder jobs / broadcast events).
      const res = await fetch(checkUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ user_id: cfg.userId || null })
      });
      const data = await safeJson(res);
      if (data && data.due) {
        openReminderModal(data.due);
      }
      if (data && data.server_now_ts && data.next_due_ts) {
        setExactTimerFromServer(data.server_now_ts, data.next_due_ts);
      } else {
        clearExactTimer();
      }
    } catch {
      // Silent: reminders should never break CRM UI.
    }
  };

  // Start realtime listener immediately (if Reverb is enabled).
  attachRealtimeListener();

  // Restore any cached due reminder immediately (before network), so hard reloads
  // do not hide the modal until the user closes it or the call reaches a terminal state.
  restoreFromCache();

  // Run as soon as the script loads so the modal comes back even if cache is empty.
  runOnce();
  setTimeout(runOnce, 1500);

  if (cfg.fpReminderUseInterval) {
    // Keep it lightweight; server decides if anything is due.
    setInterval(runOnce, 60 * 1000);
  }
})();

