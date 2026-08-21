document.getElementById('yr').textContent=new Date().getFullYear();
const nav=document.getElementById('nav');
addEventListener('scroll',()=>nav.classList.toggle('solid',scrollY>40),{passive:true});
const io=new IntersectionObserver(es=>es.forEach(e=>{if(e.isIntersecting){e.target.classList.add('in');io.unobserve(e.target);}}),{rootMargin:'0px 0px -8% 0px',threshold:.12});
document.querySelectorAll('.rv').forEach(el=>io.observe(el));
setTimeout(()=>document.querySelectorAll('.hero .rv,.phead .rv').forEach(el=>el.classList.add('in')),90);
// slideshow da capa
(()=>{const s=[...document.querySelectorAll('.hero-bg .slide')],d=document.getElementById('hero-dots');
if(s.length<2)return;let i=0,t;
s.forEach((_,k)=>{const b=document.createElement('button');b.type='button';b.setAttribute('aria-label','Foto '+(k+1));if(!k)b.className='on';b.onclick=()=>go(k);d.appendChild(b)});
const dots=[...d.children];
function go(n){s[i].classList.remove('on');dots[i].classList.remove('on');i=n;s[i].classList.add('on');dots[i].classList.add('on');clearTimeout(t);t=setTimeout(()=>go((i+1)%s.length),6500)}
t=setTimeout(()=>go(1),6500);})();

// menu mobile
(()=>{const b=document.getElementById('burger'),l=document.getElementById('nav-links');
if(!b||!l)return;
const set=o=>{l.classList.toggle('open',o);b.classList.toggle('on',o);b.setAttribute('aria-expanded',o);b.setAttribute('aria-label',o?'Fechar menu':'Abrir menu')};
b.addEventListener('click',()=>set(!l.classList.contains('open')));
l.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>set(false)));
addEventListener('keydown',e=>{if(e.key==='Escape')set(false)});})();
