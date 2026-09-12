import { chromium } from '/home/admin/.npm/_npx/e41f203b7505f1fb/node_modules/playwright-core/index.mjs';
const EXE='/home/admin/.cache/ms-playwright/chromium-1243/chrome-linux64/chrome';
const BASE='https://nileshportfolio.duckdns.org';
const path=process.argv[2]||'/';
const b=await chromium.launch({headless:true,executablePath:EXE,args:['--no-sandbox','--disable-dev-shm-usage']});
const ctx=await b.newContext({viewport:{width:1440,height:900},ignoreHTTPSErrors:true});
const p=await ctx.newPage();
await p.goto(BASE+path,{waitUntil:'networkidle',timeout:45000});
await p.waitForTimeout(800);
const out=await p.evaluate(()=>{
  const secs=[...document.querySelectorAll('body > *, main > *, main section, .site-main > *')].map(el=>{
    const r=el.getBoundingClientRect(); const cs=getComputedStyle(el);
    return {tag:el.tagName.toLowerCase(),cls:(el.className||'').toString().slice(0,70),h:Math.round(r.height),y:Math.round(r.top+scrollY),bg:cs.backgroundColor};
  }).filter(s=>s.h>4);
  const hs=[...document.querySelectorAll('h1,h2,h3,h4')].map(h=>{
    const cs=getComputedStyle(h); const r=h.getBoundingClientRect();
    return {t:h.tagName,fam:cs.fontFamily.split(',')[0].replace(/"/g,''),sz:cs.fontSize,w:cs.fontWeight,ls:cs.letterSpacing,color:cs.color,txt:h.textContent.trim().slice(0,72),y:Math.round(r.top+scrollY)};
  });
  const links=[...document.querySelectorAll('a')].length;
  const imgs=[...document.querySelectorAll('img')].length;
  const svgs=[...document.querySelectorAll('svg')].length;
  const bodyCS=getComputedStyle(document.body);
  return {h:document.documentElement.scrollHeight, secs, hs, links, imgs, svgs, bodyFont:bodyCS.fontFamily, bodyBg:bodyCS.backgroundColor, bodyColor:bodyCS.color,
    counts:{sections:document.querySelectorAll('section').length, articles:document.querySelectorAll('article').length, cards:document.querySelectorAll('[class*=card],[class*=tile]').length}};
});
console.log('PAGE:',path,'height',out.h,'| body',out.bodyFont.split(',')[0],'bg',out.bodyBg,'fg',out.bodyColor);
console.log('counts:',JSON.stringify(out.counts),'links',out.links,'imgs',out.imgs,'svg',out.svgs);
console.log('\n-- TOP-LEVEL BLOCKS --');
out.secs.forEach(s=>console.log(`  y=${String(s.y).padStart(5)} h=${String(s.h).padStart(5)} <${s.tag} class="${s.cls}"> bg=${s.bg}`));
console.log('\n-- HEADING OUTLINE --');
out.hs.forEach(h=>console.log(`  y=${String(h.y).padStart(5)} ${h.t} ${h.sz}/${h.w} ls=${h.ls} ${h.fam} | ${h.txt}`));
await b.close();
