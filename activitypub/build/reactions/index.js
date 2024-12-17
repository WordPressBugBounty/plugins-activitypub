(()=>{"use strict";var e,t={505:(e,t,a)=>{const r=window.wp.blocks,n=window.React,l=window.wp.blockEditor,o=window.wp.element,s=window.wp.i18n,i=window.wp.components,c=window.wp.apiFetch;var u=a.n(c);const{namespace:m}=window._activityPubOptions,h=({reactions:e})=>{const[t,a]=(0,o.useState)(new Set),[r,l]=(0,o.useState)(new Map),s=(0,o.useRef)([]),i=()=>{s.current.forEach((e=>clearTimeout(e))),s.current=[]},c=(t,r)=>{i();const n=100,o=e.length;r&&l((e=>{const a=new Map(e);return a.set(t,"clockwise"),a}));const c=e=>{const i="right"===e,c=i?o-1:0,u=i?1:-1;for(let e=i?t:t-1;i?e<=c:e>=c;e+=u){const o=Math.abs(e-t),i=setTimeout((()=>{a((t=>{const a=new Set(t);return r?a.add(e):a.delete(e),a})),r&&e!==t&&l((t=>{const a=new Map(t),r=e-u,n=a.get(r);return a.set(e,"clockwise"===n?"counter":"clockwise"),a}))}),o*n);s.current.push(i)}};if(c("right"),c("left"),!r){const e=Math.max((o-t)*n,t*n),a=setTimeout((()=>{l(new Map)}),e+n);s.current.push(a)}};return(0,o.useEffect)((()=>()=>i()),[]),(0,n.createElement)("ul",{className:"reaction-avatars"},e.map(((e,a)=>{const l=r.get(a),o=["reaction-avatar",t.has(a)?"wave-active":"",l?`rotate-${l}`:""].filter(Boolean).join(" ");return(0,n.createElement)("li",{key:a},(0,n.createElement)("a",{href:e.url,target:"_blank",rel:"noopener noreferrer",onMouseEnter:()=>c(a,!0),onMouseLeave:()=>c(a,!1)},(0,n.createElement)("img",{src:e.avatar,alt:e.name,className:o,width:"32",height:"32"})))})))},p=({reactions:e,type:t})=>(0,n.createElement)("ul",{className:"reaction-list"},e.map(((e,t)=>(0,n.createElement)("li",{key:t},(0,n.createElement)("a",{href:e.url,className:"reaction-item",target:"_blank",rel:"noopener noreferrer"},(0,n.createElement)("img",{src:e.avatar,alt:e.name,width:"32",height:"32"}),(0,n.createElement)("span",null,e.name)))))),f=({items:e,label:t})=>{const[a,r]=(0,o.useState)(!1),[l,s]=(0,o.useState)(null),[c,u]=(0,o.useState)(e.length),m=(0,o.useRef)(null);(0,o.useEffect)((()=>{if(!m.current)return;const t=()=>{const t=m.current;if(!t)return;const a=t.offsetWidth-(l?.offsetWidth||0)-12,r=Math.max(1,Math.floor((a-32)/22));u(Math.min(r,e.length))};t();const a=new ResizeObserver(t);return a.observe(m.current),()=>{a.disconnect()}}),[l,e.length]);const f=e.slice(0,c);return(0,n.createElement)("div",{className:"reaction-group",ref:m},(0,n.createElement)(h,{reactions:f}),(0,n.createElement)(i.Button,{ref:s,className:"reaction-label is-link",onClick:()=>r(!a),"aria-expanded":a},t),a&&l&&(0,n.createElement)(i.Popover,{anchor:l,onClose:()=>r(!1)},(0,n.createElement)(p,{reactions:e})))};function d({title:e="",postId:t=null,isEditing:a=!1,setTitle:r=(()=>{}),reactions:i=null}){const[c,h]=(0,o.useState)(i),[p,d]=(0,o.useState)(!i);return(0,o.useEffect)((()=>{if(i)return h(i),void d(!1);t?(d(!0),u()({path:`/${m}/posts/${t}/reactions`}).then((e=>{h(e),d(!1)})).catch((()=>d(!1)))):d(!1)}),[t,i]),p?null:c&&Object.values(c).some((e=>e.items?.length>0))?(0,n.createElement)("div",{className:"activitypub-reactions"},a?(0,n.createElement)(l.RichText,{tagName:"h6",value:e,onChange:r,placeholder:(0,s.__)("Fediverse reactions","activitypub"),disableLineBreaks:!0,allowedFormats:[]}):e&&(0,n.createElement)("h6",null,e),Object.entries(c).map((([e,t])=>t.items?.length?(0,n.createElement)(f,{key:e,items:t.items,label:t.label}):null))):null}const g=e=>{const t=["#FF6B6B","#4ECDC4","#45B7D1","#96CEB4","#FFEEAD","#D4A5A5","#9B59B6","#3498DB","#E67E22"],a=(()=>{const e=["Bouncy","Cosmic","Dancing","Fluffy","Giggly","Hoppy","Jazzy","Magical","Nifty","Perky","Quirky","Sparkly","Twirly","Wiggly","Zippy"],t=["Badger","Capybara","Dolphin","Echidna","Flamingo","Giraffe","Hedgehog","Iguana","Jellyfish","Koala","Lemur","Manatee","Narwhal","Octopus","Penguin"];return`${e[Math.floor(Math.random()*e.length)]} ${t[Math.floor(Math.random()*t.length)]}`})(),r=t[Math.floor(Math.random()*t.length)],n=a.charAt(0),l=document.createElement("canvas");l.width=64,l.height=64;const o=l.getContext("2d");return o.fillStyle=r,o.beginPath(),o.arc(32,32,32,0,2*Math.PI),o.fill(),o.fillStyle="#FFFFFF",o.font="32px sans-serif",o.textAlign="center",o.textBaseline="middle",o.fillText(n,32,32),{name:a,url:"#",avatar:l.toDataURL()}},v=JSON.parse('{"UU":"activitypub/reactions"}');(0,r.registerBlockType)(v.UU,{edit:function({attributes:e,setAttributes:t,__unstableLayoutClassNames:a}){const r=(0,l.useBlockProps)({className:a}),[s]=(0,o.useState)({likes:{label:"9 likes",items:Array.from({length:9},((e,t)=>g()))},reposts:{label:"6 reposts",items:Array.from({length:6},((e,t)=>g()))}});return(0,n.createElement)("div",{...r},(0,n.createElement)(d,{isEditing:!0,title:e.title,setTitle:e=>t({title:e}),reactions:s}))}})}},a={};function r(e){var n=a[e];if(void 0!==n)return n.exports;var l=a[e]={exports:{}};return t[e](l,l.exports,r),l.exports}r.m=t,e=[],r.O=(t,a,n,l)=>{if(!a){var o=1/0;for(u=0;u<e.length;u++){for(var[a,n,l]=e[u],s=!0,i=0;i<a.length;i++)(!1&l||o>=l)&&Object.keys(r.O).every((e=>r.O[e](a[i])))?a.splice(i--,1):(s=!1,l<o&&(o=l));if(s){e.splice(u--,1);var c=n();void 0!==c&&(t=c)}}return t}l=l||0;for(var u=e.length;u>0&&e[u-1][2]>l;u--)e[u]=e[u-1];e[u]=[a,n,l]},r.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return r.d(t,{a:t}),t},r.d=(e,t)=>{for(var a in t)r.o(t,a)&&!r.o(e,a)&&Object.defineProperty(e,a,{enumerable:!0,get:t[a]})},r.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{var e={608:0,104:0};r.O.j=t=>0===e[t];var t=(t,a)=>{var n,l,[o,s,i]=a,c=0;if(o.some((t=>0!==e[t]))){for(n in s)r.o(s,n)&&(r.m[n]=s[n]);if(i)var u=i(r)}for(t&&t(a);c<o.length;c++)l=o[c],r.o(e,l)&&e[l]&&e[l][0](),e[l]=0;return r.O(u)},a=globalThis.webpackChunkwordpress_activitypub=globalThis.webpackChunkwordpress_activitypub||[];a.forEach(t.bind(null,0)),a.push=t.bind(null,a.push.bind(a))})();var n=r.O(void 0,[104],(()=>r(505)));n=r.O(n)})();