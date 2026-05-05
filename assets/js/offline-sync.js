(function() {
  const DB_NAME = 'projekt1-offline';
  const DB_VERSION = 1;
  const STORE_NAME = 'pending-requests';

  function openDb() {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open(DB_NAME, DB_VERSION);

      request.onupgradeneeded = () => {
        const db = request.result;
        if (!db.objectStoreNames.contains(STORE_NAME)) {
          db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
        }
      };

      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  async function withStore(mode, callback) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
      const tx = db.transaction(STORE_NAME, mode);
      const store = tx.objectStore(STORE_NAME);
      const result = callback(store);

      tx.oncomplete = () => resolve(result);
      tx.onerror = () => reject(tx.error);
    });
  }

  function serializeForm(form, submitter) {
    const formData = submitter ? new FormData(form, submitter) : new FormData(form);
    return Array.from(formData.entries()).map(([name, value]) => ({
      name,
      value: value instanceof File ? null : value
    }));
  }

  function toFormData(fields) {
    const formData = new FormData();
    fields.forEach((field) => {
      formData.append(field.name, field.value || '');
    });
    return formData;
  }

  async function queueForm(form, submitter) {
    const payload = {
      url: form.action || window.location.href,
      method: (form.method || 'POST').toUpperCase(),
      fields: serializeForm(form, submitter),
      createdAt: new Date().toISOString(),
      pageTitle: document.title || 'Lieferschein'
    };

    await withStore('readwrite', (store) => store.add(payload));
    updatePendingBadge();
  }

  async function getQueuedRequests() {
    const db = await openDb();
    return new Promise((resolve, reject) => {
      const tx = db.transaction(STORE_NAME, 'readonly');
      const store = tx.objectStore(STORE_NAME);
      const request = store.getAll();

      request.onsuccess = () => resolve(request.result || []);
      request.onerror = () => reject(request.error);
    });
  }

  async function deleteQueuedRequest(id) {
    await withStore('readwrite', (store) => store.delete(id));
  }

  async function updatePendingBadge() {
    const badge = document.querySelector('[data-offline-sync-badge]');
    if (!badge) {
      return;
    }

    try {
      const requests = await getQueuedRequests();
      if (requests.length) {
        badge.hidden = false;
        badge.textContent = `${requests.length} offline gespeichert`;
      } else {
        badge.hidden = true;
      }
    } catch (error) {
      badge.hidden = true;
    }
  }

  async function syncQueuedRequests() {
    if (!navigator.onLine) {
      return;
    }

    const requests = await getQueuedRequests();
    for (const request of requests) {
      try {
        const response = await fetch(request.url, {
          method: request.method,
          body: toFormData(request.fields),
          credentials: 'same-origin',
          redirect: 'follow'
        });

        if (response.ok || response.redirected) {
          await deleteQueuedRequest(request.id);
        }
      } catch (error) {
        break;
      }
    }

    updatePendingBadge();
  }

  function attachOfflineForms() {
    document.querySelectorAll('form[data-offline-sync="lieferschein"]').forEach((form) => {
      let submitting = false;

      form.addEventListener('submit', async (event) => {
        if (submitting) {
          return;
        }

        event.preventDefault();
        submitting = true;

        try {
          if (!navigator.onLine) {
            throw new Error('offline');
          }

          const response = await fetch(form.action || window.location.href, {
            method: (form.method || 'POST').toUpperCase(),
            body: event.submitter ? new FormData(form, event.submitter) : new FormData(form),
            credentials: 'same-origin',
            redirect: 'follow'
          });

          if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
          }

          window.location.href = response.url || form.action || window.location.href;
        } catch (error) {
          try {
            await queueForm(form, event.submitter);
            alert('Keine Verbindung zum Server: Der Lieferschein wurde offline gespeichert und wird spaeter synchronisiert.');
          } catch (queueError) {
            alert('Offline-Speicherung fehlgeschlagen. Bitte die Seite geoeffnet lassen und erneut versuchen.');
          } finally {
            submitting = false;
          }
        }
      });
    });
  }

  window.Projekt1OfflineSync = {
    sync: syncQueuedRequests,
    updatePendingBadge
  };

  document.addEventListener('DOMContentLoaded', () => {
    attachOfflineForms();
    updatePendingBadge();
    syncQueuedRequests();
  });

  window.addEventListener('online', syncQueuedRequests);
})();
