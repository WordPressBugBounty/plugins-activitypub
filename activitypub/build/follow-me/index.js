(()=>{"use strict";var e,t={399:(e,t,r)=>{const o=window.wp.blocks,n=window.wp.primitives;var a=r(848);const l=(0,a.jsx)(n.SVG,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24",children:(0,a.jsx)(n.Path,{d:"M15.5 9.5a1 1 0 100-2 1 1 0 000 2zm0 1.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5zm-2.25 6v-2a2.75 2.75 0 00-2.75-2.75h-4A2.75 2.75 0 003.75 15v2h1.5v-2c0-.69.56-1.25 1.25-1.25h4c.69 0 1.25.56 1.25 1.25v2h1.5zm7-2v2h-1.5v-2c0-.69-.56-1.25-1.25-1.25H15v-1.5h2.5A2.75 2.75 0 0120.25 15zM9.5 8.5a1 1 0 11-2 0 1 1 0 012 0zm1.5 0a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z",fillRule:"evenodd"})});var i=r(609);const c=window.wp.blockEditor,s=window.wp.i18n,u=window.wp.data,p=window.wp.coreData,m=window.wp.components,d=window.wp.element,v=window._activityPubOptions?.enabled,f=window.wp.apiFetch;var y=r.n(f);function b(e){return`var(--wp--preset--color--${e})`}function _(e){if("string"!=typeof e)return null;if(e.match(/^#/))return e.substring(0,7);const[,,t]=e.split("|");return b(t)}function w(e,t,r=null,o=""){return r?`${e}${o} { ${t}: ${r}; }\n`:""}function h(e,t,r,o){return w(e,"background-color",t)+w(e,"color",r)+w(e,"background-color",o,":hover")+w(e,"background-color",o,":focus")}function g({selector:e,style:t,backgroundColor:r}){const o=function(e,t,r){const o=`${e} .components-button`,n=("string"==typeof(a=r)?b(a):a?.color?.background||null)||t?.color?.background;var a;return h(o,_(t?.elements?.link?.color?.text),n,_(t?.elements?.link?.[":hover"]?.color?.text))}(e,t,r);return(0,i.createElement)("style",null,o)}const E=(0,a.jsx)(n.SVG,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24",children:(0,a.jsx)(n.Path,{fillRule:"evenodd",clipRule:"evenodd",d:"M5 4.5h11a.5.5 0 0 1 .5.5v11a.5.5 0 0 1-.5.5H5a.5.5 0 0 1-.5-.5V5a.5.5 0 0 1 .5-.5ZM3 5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5Zm17 3v10.75c0 .69-.56 1.25-1.25 1.25H6v1.5h12.75a2.75 2.75 0 0 0 2.75-2.75V8H20Z"})}),k=(0,a.jsx)(n.SVG,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24",children:(0,a.jsx)(n.Path,{d:"M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z"})}),x=(0,d.forwardRef)((function({icon:e,size:t=24,...r},o){return(0,d.cloneElement)(e,{width:t,height:t,...r,ref:o})})),S=window.wp.compose,O="fediverse-remote-user";function C(e){try{return new URL(e),!0}catch(e){return!1}}function I({actionText:e,copyDescription:t,handle:r,resourceUrl:o,myProfile:n=!1,rememberProfile:a=!1}){const c=(0,s.__)("Loading...","activitypub"),u=(0,s.__)("Opening...","activitypub"),p=(0,s.__)("Error","activitypub"),v=(0,s.__)("Invalid","activitypub"),f=n||(0,s.__)("My Profile","activitypub"),[b,_]=(0,d.useState)(e),[w,h]=(0,d.useState)(E),g=(0,S.useCopyToClipboard)(r,(()=>{h(k),setTimeout((()=>h(E)),1e3)})),[I,N]=(0,d.useState)(""),[R,U]=(0,d.useState)(!0),{setRemoteUser:P}=function(){const[e,t]=(0,d.useState)(function(){const e=localStorage.getItem(O);return e?JSON.parse(e):{}}()),r=(0,d.useCallback)((e=>{!function(e){localStorage.setItem(O,JSON.stringify(e))}(e),t(e)}),[]),o=(0,d.useCallback)((()=>{localStorage.removeItem(O),t({})}),[]);return{template:e?.template||!1,profileURL:e?.profileURL||!1,setRemoteUser:r,deleteRemoteUser:o}}(),T=(0,d.useCallback)((()=>{let t;if(!C(I)&&!function(e){const t=e.replace(/^@/,"").split("@");return 2===t.length&&C(`https://${t[1]}`)}(I))return _(v),t=setTimeout((()=>_(e)),2e3),()=>clearTimeout(t);const r=o+I;_(c),y()({path:r}).then((({url:t,template:r})=>{R&&P({profileURL:I,template:r}),_(u),setTimeout((()=>{window.open(t,"_blank"),_(e)}),200)})).catch((()=>{_(p),setTimeout((()=>_(e)),2e3)}))}),[I]);return(0,i.createElement)("div",{className:"activitypub__dialog"},(0,i.createElement)("div",{className:"activitypub-dialog__section"},(0,i.createElement)("h4",null,f),(0,i.createElement)("div",{className:"activitypub-dialog__description"},t),(0,i.createElement)("div",{className:"activitypub-dialog__button-group"},(0,i.createElement)("input",{type:"text",value:r,readOnly:!0}),(0,i.createElement)(m.Button,{ref:g},(0,i.createElement)(x,{icon:w}),(0,s.__)("Copy","activitypub")))),(0,i.createElement)("div",{className:"activitypub-dialog__section"},(0,i.createElement)("h4",null,(0,s.__)("Your Profile","activitypub")),(0,i.createElement)("div",{className:"activitypub-dialog__description"},(0,d.createInterpolateElement)((0,s.__)("Or, if you know your own profile, we can start things that way! (eg <code>@yourusername@example.com</code>)","activitypub"),{code:(0,i.createElement)("code",null)})),(0,i.createElement)("div",{className:"activitypub-dialog__button-group"},(0,i.createElement)("input",{type:"text",value:I,onKeyDown:e=>{"Enter"===e?.code&&T()},onChange:e=>N(e.target.value)}),(0,i.createElement)(m.Button,{onClick:T},(0,i.createElement)(x,{icon:l}),b)),a&&(0,i.createElement)("div",{className:"activitypub-dialog__remember"},(0,i.createElement)(m.CheckboxControl,{checked:R,label:(0,s.__)("Remember me for easier comments","activitypub"),onChange:()=>{U(!R)}}))))}const{namespace:N}=window._activityPubOptions,R={avatar:"",webfinger:"@well@hello.dolly",name:(0,s.__)("Hello Dolly Fan Account","activitypub"),url:"#"};function U(e){if(!e)return R;const t={...R,...e};return t.avatar=t?.icon?.url,t}function P({profile:e,popupStyles:t,userId:r}){const{webfinger:o,avatar:n,name:a}=e,l=o.startsWith("@")?o:`@${o}`;return(0,i.createElement)("div",{className:"activitypub-profile"},(0,i.createElement)("img",{className:"activitypub-profile__avatar",src:n,alt:a}),(0,i.createElement)("div",{className:"activitypub-profile__content"},(0,i.createElement)("div",{className:"activitypub-profile__name"},a),(0,i.createElement)("div",{className:"activitypub-profile__handle",title:l},l)),(0,i.createElement)(T,{profile:e,popupStyles:t,userId:r}))}function T({profile:e,popupStyles:t,userId:r}){const[o,n]=(0,d.useState)(!1),a=(0,s.sprintf)((0,s.__)("Follow %s","activitypub"),e?.name);return(0,i.createElement)(i.Fragment,null,(0,i.createElement)(m.Button,{className:"activitypub-profile__follow",onClick:()=>n(!0)},(0,s.__)("Follow","activitypub")),o&&(0,i.createElement)(m.Modal,{className:"activitypub-profile__confirm activitypub__modal",onRequestClose:()=>n(!1),title:a},(0,i.createElement)($,{profile:e,userId:r}),(0,i.createElement)("style",null,t)))}function $({profile:e,userId:t}){const{webfinger:r}=e,o=(0,s.__)("Follow","activitypub"),n=`/${N}/actors/${t}/remote-follow?resource=`,a=(0,s.__)("Copy and paste my profile into the search field of your favorite fediverse app or server.","activitypub"),l=r.startsWith("@")?r:`@${r}`;return(0,i.createElement)(I,{actionText:o,copyDescription:a,handle:l,resourceUrl:n})}function j({selectedUser:e,style:t,backgroundColor:r,id:o,useId:n=!1,profileData:a=!1}){const[l,c]=(0,d.useState)(U()),s="site"===e?0:e,u=function(e){return h(".apfmd__button-group .components-button",_(e?.elements?.link?.color?.text)||"#111","#fff",_(e?.elements?.link?.[":hover"]?.color?.text)||"#333")}(t),p=n?{id:o}:{};function m(e){c(U(e))}return(0,d.useEffect)((()=>{if(a)return m(a);(function(e){const t={headers:{Accept:"application/activity+json"},path:`/${N}/actors/${e}`};return y()(t)})(s).then(m)}),[s,a]),(0,i.createElement)("div",{...p},(0,i.createElement)(g,{selector:`#${o}`,style:t,backgroundColor:r}),(0,i.createElement)(P,{profile:l,userId:s,popupStyles:u}))}function B({name:e}){const t=(0,s.sprintf)(/* translators: %s: block name */
"This <strong>%s</strong> block will adapt to the page it is on, displaying the user profile associated with a post author (in a loop) or a user archive. It will be <strong>empty</strong> in other non-author contexts.",e);return(0,i.createElement)(m.Card,null,(0,i.createElement)(m.CardBody,null,(0,d.createInterpolateElement)(t,{strong:(0,i.createElement)("strong",null)})))}(0,o.registerBlockType)("activitypub/follow-me",{edit:function({attributes:e,setAttributes:t,context:{postType:r,postId:o}}){const n=(0,c.useBlockProps)({className:"activitypub-follow-me-block-wrapper"}),a=function({withInherit:e=!1}){const t=v?.users?(0,u.useSelect)((e=>e("core").getUsers({who:"authors"}))):[];return(0,d.useMemo)((()=>{if(!t)return[];const r=[];return v?.site&&r.push({label:(0,s.__)("Site","activitypub"),value:"site"}),e&&v?.users&&r.push({label:(0,s.__)("Dynamic User","activitypub"),value:"inherit"}),t.reduce(((e,t)=>(e.push({label:t.name,value:`${t.id}`}),e)),r)}),[t])}({withInherit:!0}),{selectedUser:l}=e,f="inherit"===l,y=(0,u.useSelect)((e=>{const{getEditedEntityRecord:t}=e(p.store),n=t("postType",r,o)?.author;return null!=n?n:null}),[r,o]);return(0,d.useEffect)((()=>{a.length&&(a.find((({value:e})=>e===l))||t({selectedUser:a[0].value}))}),[l,a]),(0,i.createElement)("div",{...n},a.length>1&&(0,i.createElement)(c.InspectorControls,{key:"setting"},(0,i.createElement)(m.PanelBody,{title:(0,s.__)("Followers Options","activitypub")},(0,i.createElement)(m.SelectControl,{label:(0,s.__)("Select User","activitypub"),value:e.selectedUser,options:a,onChange:e=>t({selectedUser:e})}))),f?y?(0,i.createElement)(j,{...e,id:n.id,selectedUser:y}):(0,i.createElement)(B,{name:(0,s.__)("Follow Me","activitypub")}):(0,i.createElement)(j,{...e,id:n.id}))},save:()=>null,icon:l})},20:(e,t,r)=>{var o=r(609),n=Symbol.for("react.element"),a=(Symbol.for("react.fragment"),Object.prototype.hasOwnProperty),l=o.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED.ReactCurrentOwner,i={key:!0,ref:!0,__self:!0,__source:!0};t.jsx=function(e,t,r){var o,c={},s=null,u=null;for(o in void 0!==r&&(s=""+r),void 0!==t.key&&(s=""+t.key),void 0!==t.ref&&(u=t.ref),t)a.call(t,o)&&!i.hasOwnProperty(o)&&(c[o]=t[o]);if(e&&e.defaultProps)for(o in t=e.defaultProps)void 0===c[o]&&(c[o]=t[o]);return{$$typeof:n,type:e,key:s,ref:u,props:c,_owner:l.current}}},848:(e,t,r)=>{e.exports=r(20)},609:e=>{e.exports=window.React}},r={};function o(e){var n=r[e];if(void 0!==n)return n.exports;var a=r[e]={exports:{}};return t[e](a,a.exports,o),a.exports}o.m=t,e=[],o.O=(t,r,n,a)=>{if(!r){var l=1/0;for(u=0;u<e.length;u++){for(var[r,n,a]=e[u],i=!0,c=0;c<r.length;c++)(!1&a||l>=a)&&Object.keys(o.O).every((e=>o.O[e](r[c])))?r.splice(c--,1):(i=!1,a<l&&(l=a));if(i){e.splice(u--,1);var s=n();void 0!==s&&(t=s)}}return t}a=a||0;for(var u=e.length;u>0&&e[u-1][2]>a;u--)e[u]=e[u-1];e[u]=[r,n,a]},o.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return o.d(t,{a:t}),t},o.d=(e,t)=>{for(var r in t)o.o(t,r)&&!o.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},o.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{var e={338:0,301:0};o.O.j=t=>0===e[t];var t=(t,r)=>{var n,a,[l,i,c]=r,s=0;if(l.some((t=>0!==e[t]))){for(n in i)o.o(i,n)&&(o.m[n]=i[n]);if(c)var u=c(o)}for(t&&t(r);s<l.length;s++)a=l[s],o.o(e,a)&&e[a]&&e[a][0](),e[a]=0;return o.O(u)},r=globalThis.webpackChunkwordpress_activitypub=globalThis.webpackChunkwordpress_activitypub||[];r.forEach(t.bind(null,0)),r.push=t.bind(null,r.push.bind(r))})();var n=o.O(void 0,[301],(()=>o(399)));n=o.O(n)})();