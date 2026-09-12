import { chromium } from '/home/admin/.npm/_npx/e41f203b7505f1fb/node_modules/playwright-core/index.mjs';
const EXE='/home/admin/.cache/ms-playwright/chromium-1243/chrome-linux64/chrome';
const BASE='https://nileshportfolio.duckdns.org';
const pages=process.argv[2]?process.argv[2].split(','):['/','/projects/','/docs/','/about/','/contact/','/blog/'];
const w=parseInt(process.argv[3]||'1440'), h=parseInt(process.argv[4]||'900');
const tag=process.argv[5]||'cur';
const b=await chromium.launch({headless:true,executablePath:EXE,args:['--no-sandbox','--disable-dev-shm-usage']});
const ctx=await b.newContext({viewport:{width:w,height:h},ignoreHTTPSErrors:true,deviceScaleFactor:1});
for(const path of pages){
  const p=await ctx.newPage();
  const errs=[];
  p.on('console',m=>{if(m.type()==='error')errs.push(m.text().slice(0,160));});
  p.on('pageerror',e=>errs.push('PAGEERROR '+e.message.slice(0,160)));
  try{
    const r=await p.goto(BASE+path,{waitUntil:'networkidle',timeout:45000});
    await p.waitForTimeout(1200);
    const name=tag+'-'+(path.replace(/[^a-z0-9]/gi,'_')||'home')+`-${w}`;
    await p.screenshot({path:`shots/${name}.png`,fullPage:true});
    const dim=await p.evaluate(()=>({h:document.documentElement.scrollHeight,title:document.title}));
    console.log(`${r.status()} ${path} -> ${name}.png  (${dim.h}px) "${dim.title}"`);
    if(errs.length)console.log('   ERR:',errs.slice(0,3).join(' | '));
  }catch(e){console.log('FAIL '+path+': '+e.message.slice(0,120));}
  await p.close();
}
await b.close();
