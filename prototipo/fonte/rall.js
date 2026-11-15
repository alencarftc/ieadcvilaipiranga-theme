// Uso: node rall.js [largura]  (1440 = computador, 390 = celular)
const {chromium}=require('playwright');const fs=require('fs');
const D='/mnt/project-files/website/prototipo-v3/';const W=+(process.argv[2]||1440);
const OUT=D+(W<800?'png-celular/':'png/');fs.mkdirSync(OUT,{recursive:true});
(async()=>{const b=await chromium.launch();const p=await b.newPage({viewport:{width:W,height:900},deviceScaleFactor:W<800?2:1});
for(const f of fs.readdirSync(D).filter(f=>/\.html$/.test(f)).sort()){
await p.goto('file://'+D+f);await p.waitForTimeout(400);
const ow=await p.evaluate(()=>document.documentElement.scrollWidth);if(ow>W)console.log('OVERFLOW',f,ow);
await p.screenshot({path:OUT+f.replace('.html',W<800?'.jpg':'.jpg'),fullPage:true,...(W<800?{type:'jpeg',quality:60}:{type:'jpeg',quality:70})})}
await b.close()})();
