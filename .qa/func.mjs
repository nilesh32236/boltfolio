import { chromium } from '/home/admin/.npm/_npx/e41f203b7505f1fb/node_modules/playwright-core/index.mjs';
const EXE='/home/admin/.cache/ms-playwright/chromium-1243/chrome-linux64/chrome';
const BASE='https://nileshportfolio.duckdns.org';
const b=await chromium.launch({headless:true,executablePath:EXE,args:['--no-sandbox','--disable-dev-shm-usage']});
// ---- mobile ----
for (const vp of [{w:390,h:844,n:'iPhone390'},{w:768,h:1024,n:'iPad768'}]){
  const ctx=await b.newContext({viewport:{width:vp.w,height:vp.h},ignoreHTTPSErrors:true,isMobile:vp.w<500,hasTouch:vp.w<500});
  const p=await ctx.newPage();
  await p.goto(BASE+'/',{waitUntil:'networkidle',timeout:40000});
  const m=await p.evaluate(()=>{
    const nav=document.getElementById('site-nav'); const t=document.querySelector('.nav-toggle');
    const overflow=document.documentElement.scrollWidth>window.innerWidth+1;
    const wide=[...document.querySelectorAll('body *')].filter(e=>e.getBoundingClientRect().right>window.innerWidth+2&&getComputedStyle(e).position!=='fixed').slice(0,4).map(e=>e.tagName+'.'+(e.className||'').toString().split(' ')[0]);
    return {toggleVisible:t?getComputedStyle(t).display!=='none':false, navOpen:nav?nav.classList.contains('is-open'):null, overflow, wide, scrollW:document.documentElement.scrollWidth, winW:window.innerWidth};
  });
  console.log(`[${vp.n}] navToggleVisible=${m.toggleVisible} overflowX=${m.overflow} scrollW=${m.scrollW}/${m.winW} bleed=${JSON.stringify(m.wide)}`);
  if(m.toggleVisible){ await p.click('.nav-toggle'); await p.waitForTimeout(400);
    const after=await p.evaluate(()=>{const n=document.getElementById('site-nav');const r=n.getBoundingClientRect();return {open:n.classList.contains('is-open'),h:Math.round(r.height),vis:getComputedStyle(n).display};});
    console.log(`   after toggle: ${JSON.stringify(after)}`); }
  await p.screenshot({path:`shots/mob-${vp.n}.png`,fullPage:false});
  await ctx.close();
}
// ---- console + search + links ----
const ctx=await b.newContext({viewport:{width:1440,height:900},ignoreHTTPSErrors:true});
const p=await ctx.newPage(); const errs=[];
p.on('console',m=>{if(m.type()==='error')errs.push(m.text().slice(0,140));});
p.on('pageerror',e=>errs.push('PAGEERR '+e.message.slice(0,140)));
p.on('response',r=>{if(r.status()>=400)errs.push('HTTP'+r.status()+' '+r.url().slice(0,90));});
for(const u of ['/','/docs/','/docs/performance-optimisation/','/docs/performance-optimisation/includes/class-main/','/projects/','/projects/performance-optimisation/','/about/','/contact/','/blog/','/search/nothingxyz/']){
  errs.length=0;
  const r=await p.goto(BASE+u,{waitUntil:'networkidle',timeout:40000});
  const info=await p.evaluate(()=>({h:document.documentElement.scrollHeight,h1:document.querySelector('h1')?.textContent.trim().slice(0,40)||'(none)',title:document.title.slice(0,50)}));
  console.log(`\n${r.status()} ${u}  h=${info.h} h1="${info.h1}"`);
  if(errs.length) console.log('   ISSUES: '+[...new Set(errs)].slice(0,4).join(' || '));
}
// search UI present?
await p.goto(BASE+'/docs/',{waitUntil:'networkidle'});
const s=await p.evaluate(()=>({searchInputs:document.querySelectorAll('input[type=search],input[name=s]').length, hasDocsSearch:!!document.querySelector('.docs-search,[data-docs-search]'), sidebar:!!document.querySelector('.docs-sidebar')}));
console.log('\n/docs/ UI:',JSON.stringify(s));
await b.close();
