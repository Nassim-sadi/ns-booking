/* NS Booking — vanilla JS, no build */
(function(){
  const root = document.querySelector('[data-nsbc]');
  if (!root || typeof NSBC === 'undefined') return;

  const state = { packageId: null, session: 'solo', extras: new Set(), date: '' };

  const els = {
    packages: root.querySelector('[data-role="packages"]'),
    extrasWrap: root.querySelector('[data-role="extras-wrap"]'),
    extras: root.querySelector('[data-role="extras"]'),
    summaryBody: root.querySelector('[data-role="summary-body"]'),
    total: root.querySelector('[data-role="total"]'),
    date: root.querySelector('[data-role="date"]'),
    phoneCountry: root.querySelector('[data-role="phone-country"]'),
    phone: root.querySelector('[data-role="phone"]'),
    name: root.querySelector('[data-role="name"]'),
    email: root.querySelector('[data-role="email"]'),
    message: root.querySelector('[data-role="message"]'),
    honeypot: root.querySelector('[data-role="honeypot"]'),
    submit: root.querySelector('[data-role="submit"]'),
    formMsg: root.querySelector('[data-role="form-msg"]'),
    success: root.querySelector('[data-role="success"]')
  };

  const pkgs = NSBC.packages || {};
  const extrasMap = NSBC.extras || {};
  const currency = NSBC.currency || 'EUR';
  const symbol = NSBC.currencySymbol || '€';

  function fmt(cents){
    const n = (cents/100).toFixed(2);
    if (['MAD','AED','SAR'].includes(currency)) return n + ' ' + symbol;
    return symbol + n;
  }
  function esc(s){ const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
  function escAttr(s){ return String(s).replace(/"/g,'&quot;'); }

  function renderPackages(){
    if (!els.packages) return;
    const ids = Object.keys(pkgs);
    if (!ids.length){
      els.packages.innerHTML = '<p class="nsbc-summary-empty">No packages available.</p>';
      return;
    }
    els.packages.innerHTML = ids.map(id=>{
      const p = pkgs[id];
      const priceFmt = p.pricesFormatted[state.session] ?? p.pricesFormatted.solo;
      const active = String(state.packageId)===String(id) ? 'is-active' : '';
      const img = p.imageUrl ? `<img class="nsbc-package-img" src="${escAttr(p.imageUrl)}" alt="${escAttr(p.label)}" loading="lazy">` : `<div class="nsbc-package-empty-img">📦</div>`;
      const excerpt = p.excerpt ? `<div class="nsbc-package-excerpt">${esc(p.excerpt)}</div>` : '';
      return `<div class="nsbc-package ${active}" data-pkg="${id}" role="button" tabindex="0" aria-pressed="${active?'true':'false'}">
        ${img}
        <div class="nsbc-package-body">
          <div class="nsbc-package-title">${esc(p.label)}</div>
          ${excerpt}
          <div class="nsbc-package-price"><strong>${esc(priceFmt)}</strong></div>
        </div>
      </div>`;
    }).join('');
    els.packages.querySelectorAll('[data-pkg]').forEach(el=>{
      el.addEventListener('click', ()=>{ state.packageId = parseInt(el.dataset.pkg,10); state.extras.clear(); renderPackages(); renderExtras(); renderSummary(); });
      el.addEventListener('keydown', e=>{ if(e.key==='Enter'||e.key===' '){ e.preventDefault(); el.click(); }});
    });
  }

  function renderExtras(){
    if (!els.extras || !els.extrasWrap) return;
    if (!state.packageId || !pkgs[state.packageId]){
      els.extrasWrap.style.display='none'; els.extras.innerHTML=''; return;
    }
    const p = pkgs[state.packageId];
    const ids = p.extraIds || [];
    if (!ids.length){ els.extrasWrap.style.display='none'; return; }
    els.extrasWrap.style.display='';
    els.extras.innerHTML = ids.map(id=>{
      const ex = extrasMap[id];
      if (!ex) return '';
      const checked = state.extras.has(id) || state.extras.has(String(id));
      const icon = ex.iconUrl ? `<img src="${escAttr(ex.iconUrl)}" alt="">` : (ex.iconClass ? `<span class="nsbc-extra-icon dashicons ${escAttr(ex.iconClass)}"></span>` : `<span class="nsbc-extra-icon">✨</span>`);
      return `<label class="nsbc-extra ${checked?'is-checked':''}">
        <input type="checkbox" value="${id}" ${checked?'checked':''}> ${icon}
        <span>${esc(ex.label)} <small>+${esc(ex.priceFormatted)}</small></span>
      </label>`;
    }).join('');
    els.extras.querySelectorAll('input[type=checkbox]').forEach(cb=>{
      cb.addEventListener('change', ()=>{
        const id = parseInt(cb.value,10);
        if (cb.checked) state.extras.add(id); else state.extras.delete(id);
        if (cb.checked) state.extras.delete(String(id)); else state.extras.delete(String(id));
        renderExtras(); renderSummary();
      });
    });
  }

  function calcDisplayTotal(){
    if (!state.packageId || !pkgs[state.packageId]) return 0;
    const p = pkgs[state.packageId];
    let total = p.prices[state.session] ?? p.prices.solo;
    state.extras.forEach(id=>{ const ex = extrasMap[id] || extrasMap[String(id)]; if (ex) total += ex.price; });
    return total;
  }

  function renderSummary(){
    if (!els.summaryBody || !els.total) return;
    if (!state.packageId || !pkgs[state.packageId]){
      els.summaryBody.innerHTML = `<p class="nsbc-summary-empty">${esc(NSBC.i18n.selectPackage)}</p>`;
      els.total.textContent = fmt(0);
      return;
    }
    const p = pkgs[state.packageId];
    const total = calcDisplayTotal();
    const img = p.imageUrl ? `<img class="nsbc-summary-package-img" src="${escAttr(p.imageUrl)}" alt="">` : '';
    const extrasList = Array.from(state.extras).map(id=>{
      const ex = extrasMap[id] || extrasMap[String(id)];
      return ex ? `<li>${esc(ex.label)} <small>+${esc(ex.priceFormatted)}</small></li>` : '';
    }).filter(Boolean).join('');
    els.summaryBody.innerHTML = `
      ${img}
      <div><strong>${esc(p.label)}</strong> — ${esc(state.session==='couple'?'Couple':'Solo')}</div>
      <div style="margin-top:6px;font-size:13px;color:var(--nsbc-muted)">${esc(p.pricesFormatted[state.session] ?? p.pricesFormatted.solo)}</div>
      ${extrasList ? `<ul>${extrasList}</ul>` : '<div style="color:var(--nsbc-muted);margin-top:6px;font-size:13px">No extras</div>'}
      ${state.date ? `<div style="margin-top:10px">Date: <strong>${esc(state.date.split('-').reverse().join('/'))}</strong></div>` : '<div style="color:var(--nsbc-muted);margin-top:10px;font-size:13px">No date selected</div>'}
    `;
    els.total.textContent = fmt(total);
  }

  function initPhone(){
    if (!els.phoneCountry) return;
    let list = NSBC.phoneCountries || [];
    // support both [{code,flag,label}] and ["+33"]
    const normalized = list.map(c=>{
      if (typeof c === 'string') return {code:c, label:c};
      return c;
    });
    const def = NSBC.phoneDefault || (normalized[0] && normalized[0].code) || '+33';
    els.phoneCountry.innerHTML = normalized.map(o=>{
      const label = o.label || `${o.flag||''} ${o.code}`.trim();
      return `<option value="${escAttr(o.code)}" ${o.code===def?'selected':''}>${esc(label)}</option>`;
    }).join('');
  }

  const pillWrap = root.querySelector('[data-role="session-pills"]');
  if (pillWrap){
    pillWrap.querySelectorAll('input[name="nsbc_session"]').forEach(r=>{
      r.addEventListener('change', ()=>{
        state.session = r.value;
        pillWrap.querySelectorAll('.nsbc-pill').forEach(l=>l.classList.toggle('is-active', l.contains(r)));
        renderPackages(); renderSummary();
      });
    });
  }
  if (els.date) els.date.addEventListener('change', ()=>{ state.date = els.date.value; renderSummary(); });

  async function submit(){
    if (els.formMsg) els.formMsg.textContent='';
    const name = els.name ? els.name.value.trim() : '';
    const email = els.email ? els.email.value.trim() : '';
    const phoneCountry = els.phoneCountry ? els.phoneCountry.value : '';
    const phone = els.phone ? els.phone.value.trim() : '';
    const date = els.date ? els.date.value : state.date;
    const honeypot = els.honeypot ? els.honeypot.value.trim() : '';
    if (!state.packageId) { if(els.formMsg) els.formMsg.textContent = NSBC.i18n.selectPackage; return; }
    if (!date) { if(els.formMsg) els.formMsg.textContent = NSBC.i18n.required; return; }
    if (name.length < 2) { if(els.formMsg) els.formMsg.textContent = NSBC.i18n.required; return; }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { if(els.formMsg) els.formMsg.textContent = NSBC.i18n.invalidEmail; return; }
    if (!phone || phone.replace(/\D/g,'').length < 6) { if(els.formMsg) els.formMsg.textContent = NSBC.i18n.invalidPhone; return; }

    const payload = { package_id: state.packageId, session_type: state.session, extras: Array.from(state.extras), date, name, email, phone_country: phoneCountry, phone, message: els.message ? els.message.value : '', website: honeypot, total: 1 };
    if (els.submit) { els.submit.disabled=true; els.submit.textContent='…'; }
    try{
      let res, json;
      try{
        res = await fetch(NSBC.restUrl, { method:'POST', headers:{'Content-Type':'application/json','X-WP-Nonce': NSBC.restNonce}, body: JSON.stringify(payload) });
        json = await res.json();
      }catch(e){ res=null; }
      if (!res || !res.ok || !json){
        const form = new FormData();
        form.append('action','nsbc_submit'); form.append('nonce', NSBC.ajaxNonce);
        Object.entries(payload).forEach(([k,v])=>{ if (Array.isArray(v)) v.forEach(val=>form.append(k+'[]', val)); else form.append(k, v); });
        res = await fetch(NSBC.ajaxUrl, {method:'POST', body: form});
        json = await res.json();
      }
      if (json && (json.success || json.data)){
        if (els.success) els.success.style.display='';
        const layout = root.querySelector('.nsbc-layout');
        if (layout) layout.style.display='none';
        if (els.formMsg) { els.formMsg.className='nsbc-form-msg is-success'; els.formMsg.textContent=''; }
      } else {
        const msg = (json && (json.data && (json.data.message || json.data.errors || json.data))) || NSBC.i18n.submitError;
        const text = typeof msg === 'string' ? msg : (Array.isArray(msg) ? msg.join(' ') : JSON.stringify(msg));
        if (els.formMsg) els.formMsg.textContent = text;
      }
    }catch(err){ if (els.formMsg) els.formMsg.textContent = NSBC.i18n.submitError;
    }finally{ if (els.submit){ els.submit.disabled=false; els.submit.textContent='Send Booking Request'; } }
  }
  if (els.submit) els.submit.addEventListener('click', submit);

  // sticky header auto-detect → sets --nsbc-header-height for CSS sticky offset
  function updateHeaderOffset(){
    // robust: scan any fixed/sticky header near top, take max height
    const candidates = document.querySelectorAll('header, .header, #header, [role="banner"], .site-header, nav, .thegem-te-header, .gem-header, [data-elementor-type="header"]');
    let maxH = 0;
    candidates.forEach(el=>{
      const cs = getComputedStyle(el);
      const isSticky = cs.position === 'fixed' || cs.position === 'sticky';
      if (!isSticky) return;
      const r = el.getBoundingClientRect();
      // must be visible at top and wide (header, not small button)
      if (r.width < 200) return;
      if (r.top > 16) return;
      if (r.height < 30 || r.height > 400) return;
      if (r.height > maxH) maxH = Math.ceil(r.height);
    });
    // fallback: also scan any element with top:0 and fixed
    if (maxH === 0){
      document.querySelectorAll('*').forEach(el=>{
        if (maxH) return;
        const cs = getComputedStyle(el);
        if (cs.position !== 'fixed') return;
        const r = el.getBoundingClientRect();
        if (r.top === 0 && r.width > 300 && r.height > 40 && r.height < 300) maxH = Math.ceil(r.height);
      });
    }
    const adminBg = document.getElementById('wpadminbar');
    let adminH = 0;
    if (adminBg && getComputedStyle(adminBg).display !== 'none') adminH = Math.ceil(adminBg.getBoundingClientRect().height);
    const h = maxH + adminH;
    const val = h ? (h + 20) + 'px' : '24px';
    document.documentElement.style.setProperty('--nsbc-header-height', val);
    const sidebar = root.querySelector('.nsbc-sidebar');
    if (sidebar) sidebar.style.top = val;
    // also expose on root for manual override: <div data-nsbc style="--nsbc-header-height:90px">
    root.style.setProperty('--nsbc-header-height', val);
  }
  window.addEventListener('load', updateHeaderOffset);
  window.addEventListener('resize', updateHeaderOffset);
  window.addEventListener('scroll', updateHeaderOffset, {passive:true});
  const mo = new MutationObserver(updateHeaderOffset);
  mo.observe(document.body, {attributes:true, childList:true, subtree:true});
  setTimeout(updateHeaderOffset, 300); setTimeout(updateHeaderOffset, 900); setTimeout(updateHeaderOffset, 2000);

  initPhone(); renderPackages(); renderExtras(); renderSummary(); updateHeaderOffset();
})();
