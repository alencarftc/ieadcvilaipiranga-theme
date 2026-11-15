// Renderiza só as páginas de ministérios: node rmin.js
const {chromium}=require('playwright');const fs=require('fs');const D='/mnt/project-files/website/prototipo-v3/';
(async()=>{const b=await chromium.launch();
for(const W of [1440,390]){const p=await b.newPage({viewport:{width:W,height:900},deviceScaleFactor:1});const OUT=D+(W<800?'png-celular/':'png/');
for(const f of fs.readdirSync(D).filter(f=>/^ministerios.*\.html$/.test(f))){await p.goto('file://'+D+f);await p.waitForTimeout(300);
const ow=await p.evaluate(()=>document.documentElement.scrollWidth);if(ow>W)console.log('OVERFLOW',W,f,ow);
await p.screenshot({path:OUT+f.replace('.html','.jpg'),fullPage:true,type:'jpeg',quality:70})}await p.close()}
await b.close()})();
