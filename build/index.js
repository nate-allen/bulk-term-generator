(()=>{"use strict";var e={338:(e,t,a)=>{var r=a(795);t.H=r.createRoot,r.hydrateRoot},795:e=>{e.exports=window.ReactDOM}},t={};function a(r){var n=t[r];if(void 0!==n)return n.exports;var s=t[r]={exports:{}};return e[r](s,s.exports,a),s.exports}a.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return a.d(t,{a:t}),t},a.d=(e,t)=>{for(var r in t)a.o(t,r)&&!a.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},a.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{const e=window.React;var t=a(338);const r=window.wp.components,n=window.wp.i18n,s=(0,e.forwardRef)((function(t,a){const{className:r,value:s,...l}=t,o=!Number.isFinite(s);return(0,e.createElement)("div",{className:`progress-bar-track ${r}`},(0,e.createElement)("div",{className:"progress-bar-indicator "+(o?"indeterminate":"determinate"),style:{width:o?void 0:`${s}%`}}),(0,e.createElement)("progress",{max:100,value:s,"aria-label":(0,n.__)("Loading …"),ref:a,className:"progress-bar-element",...l}))})),l=window.wp.apiFetch;var o=a.n(l);const c=()=>{const[t,a]=(0,e.useState)([]),[n,l]=(0,e.useState)(""),[c,m]=(0,e.useState)([]),[i,d]=(0,e.useState)(!1),[u,p]=(0,e.useState)(""),[h,g]=(0,e.useState)(0),[E,w]=(0,e.useState)([]),[v,f]=(0,e.useState)(""),[b,y]=(0,e.useState)(!1),[N,_]=(0,e.useState)(null),[C,S]=(0,e.useState)(null),[x,T]=(0,e.useState)(0),[k,$]=(0,e.useState)(!1),[P,B]=(0,e.useState)(!1),[R,L]=(0,e.useState)({message:"",status:""}),A=(0,e.useRef)(null),M=(0,e.useRef)(null);(0,e.useEffect)((()=>{o()({path:"/bulk-term-generator/v1/taxonomies"}).then((e=>{const t=[{label:"",value:""},...Object.keys(e).map((t=>({label:e[t].label,value:t})))];a(t)}));const e=new URLSearchParams(window.location.search).get("taxonomy");e&&(l(e),O(e));const t=e=>{const t=new URLSearchParams(window.location.search).get("taxonomy");l(t),t?O(t):m([])};return window.addEventListener("popstate",t),()=>{window.removeEventListener("popstate",t)}}),[]),(0,e.useEffect)((()=>{if(A.current&&M.current){const{top:e}=M.current.getBoundingClientRect(),{top:t}=A.current.getBoundingClientRect();M.current.scrollTo({top:M.current.scrollTop+t-e,behavior:"smooth"})}}),[E]);const O=e=>{y(!0),o()({path:`/bulk-term-generator/v1/taxonomy/${e}`,parse:!1}).then((e=>(d("true"===e.headers.get("X-Hierarchical")),e.json()))).then((e=>{m(e),y(!1)})).catch((e=>{L({message:"Error fetching terms. Please try again.",status:"error"}),y(!1)}))},D=e=>{_(e)},j=e=>{const t=(e,a)=>{const r=a.filter((t=>t.parent===e));let n=r.length;return r.forEach((e=>{n+=t(e.term_id,a)})),n},a=t(e.term_id,c);let r;r=0===a?`Are you sure you want to delete the term "${e.name}"?`:1===a?`Are you sure you want to delete the term "${e.name}" and its child?`:`Are you sure you want to delete the term "${e.name}" and its ${a} children?`,window.confirm(r+"\nThis action cannot be undone!")&&(S(e.term_id),_(null),e.isNew?(w(E.filter((t=>t.term_id!==e.term_id))),S(null)):o()({path:`/bulk-term-generator/v1/terms/${n}/${e.term_id}`,method:"DELETE"}).then((()=>{m(c.filter((t=>t.term_id!==e.term_id))),L({message:`"${e.name}" successfully deleted.`,status:"info"})})).catch((t=>{L({message:`Error deleting term "${e.name}". Please try again.`,status:"error"})})).finally((()=>S(null))))},F=e=>{const t={};return e.forEach((e=>{t[e.term_id]={...e,children:[]}})),e.forEach((e=>{e.parent&&t[e.parent]&&t[e.parent].children.push(t[e.term_id])})),Object.values(t).filter((e=>0===e.parent))},U=t=>{const a=[...t].sort(((e,t)=>e.name.localeCompare(t.name)));return(0,e.createElement)("ul",{className:"term-tree"},a.map((t=>(0,e.createElement)("li",{key:t.term_id,className:t.isNew?"new-term":"",ref:t.isNew?A:null},(0,e.createElement)("div",{className:"term-content"},(0,e.createElement)("span",{className:"term-name",onClick:()=>D(t)},t.name),(0,e.createElement)(r.Button,{icon:"edit",onClick:()=>D(t),className:"edit-button",disabled:C===t.term_id}),(0,e.createElement)(r.Button,{icon:"trash",onClick:()=>j(t),className:"delete-button",disabled:C===t.term_id})),t.children.length>0&&U(t.children)))))},q=(e,t=0)=>{let a=[];return(e=e.sort(((e,t)=>e.name.localeCompare(t.name)))).forEach((e=>{a.push({label:`${"—".repeat(t)} ${e.name}`,value:e.term_id}),e.children.length>0&&(a=a.concat(q(e.children,t+1)))})),a},z=async e=>{console.log("Processing terms",e);const t=(await o()({path:"/bulk-term-generator/v1/terms/"+n,method:"POST",data:{terms:e}})).processed,a=t.reduce(((e,t)=>(e[t.old_id]=t.term_id,e)),{});return{processedTerms:t,idMap:a}},G=[...c,...E];return(0,e.createElement)("div",{className:"bulk-term-generator"},R.message&&(0,e.createElement)(r.Notice,{status:R.status,isDismissible:!0,onRemove:()=>L({message:"",status:""})},R.message),n?(0,e.createElement)(e.Fragment,null,(0,e.createElement)("div",{className:"form-and-tree-container"},!N&&(0,e.createElement)("div",{className:"add-term-form"},(0,e.createElement)("h2",null,"Add Terms"),k&&(0,e.createElement)("div",{className:"progress-bar-wrapper"},(0,e.createElement)(s,{value:x}),(0,e.createElement)("div",{className:"progress-bar-label"},x,(0,e.createElement)("span",null,"%"))),(0,e.createElement)("div",{className:"add-terms-wrapper"},(0,e.createElement)(r.TextareaControl,{label:"Add Terms (name, slug, description) - one per line",value:u,onChange:e=>{p(e)},className:"add-terms-textarea",disabled:k||P}),(0,e.createElement)(r.Button,{isPrimary:!0,onClick:()=>{P||B(!0)},className:"ai-generator"},(0,e.createElement)("span",{className:"ai-icon"},P&&(0,e.createElement)(r.Spinner,null),!P&&(0,e.createElement)("svg",{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 16 16",fill:"currentColor",width:"16px",height:"16px"},(0,e.createElement)("path",{d:"M7.657 6.247c.11-.33.576-.33.686 0l.645 1.937a2.89 2.89 0 0 0 1.829 1.828l1.936.645c.33.11.33.576 0 .686l-1.937.645a2.89 2.89 0 0 0-1.828 1.829l-.645 1.936a.361.361 0 0 1-.686 0l-.645-1.937a2.89 2.89 0 0 0-1.828-1.828l-1.937-.645a.361.361 0 0 1 0-.686l1.937-.645a2.89 2.89 0 0 0 1.828-1.828l.645-1.937zM3.794 1.148a.217.217 0 0 1 .412 0l.387 1.162c.173.518.579.924 1.097 1.097l1.162.387a.217.217 0 0 1 0 .412l-1.162.387A1.734 1.734 0 0 0 4.593 5.69l-.387 1.162a.217.217 0 0 1-.412 0L3.407 5.69A1.734 1.734 0 0 0 2.31 4.593l-1.162-.387a.217.217 0 0 1 0-.412l1.162-.387A1.734 1.734 0 0 0 3.407 2.31l.387-1.162zM10.863.099a.145.145 0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145 0 0 1 0 .274l-.774.258a1.156 1.156 0 0 0-.732.732l-.258.774a.145.145 0 0 1-.274 0l-.258-.774a1.156 1.156 0 0 0-.732-.732L9.1 2.137a.145.145 0 0 1 0-.274l.774-.258c.346-.115.617-.386.732-.732L10.863.1z"}))),P?(0,e.createElement)("span",{className:"brainstorming"},"Brainstorming"):"Brainstorm")),i&&(0,e.createElement)(r.SelectControl,{label:"Parent",value:v,options:[{label:"None",value:""},...q(F(G))],onChange:e=>f(e),className:"parent-select"}),(0,e.createElement)(r.Button,{isSecondary:!0,onClick:()=>{const e=u.split("\n").filter((e=>e.trim())).map((e=>{const[t,a,r]=(e=>{const t=[];let a="",r=!1;for(let n=0;n<e.length;n++){const s=e[n];r?(a+=s,r=!1):"\\"===s?r=!0:","===s?(t.push(a.trim()),a=""):a+=s}return t.push(a.trim()),t})(e);return{name:t,slug:a,description:r}})),t=e.map(((e,t)=>({term_id:`new-${h+t}`,name:e.name,slug:e.slug,description:e.description,parent:v||0,children:[],isNew:!0})));g((t=>t+e.length)),w((e=>{const a=t.filter((t=>!e.concat(c).find((e=>e.name===t.name&&e.parent===t.parent)))),r=1===a.length?'1 term added to the queue! Click "Generate Terms" to create them.':`${a.length} terms added to the queue! Click "Generate Terms" to create them.`;return a.length>0?L({message:r,status:"info"}):L({message:"No new terms added to the queue because they already exist.",status:"warning"}),[...e,...a]})),p(""),f("")},className:"add-terms-button",disabled:!u.trim()||k||P},"Add Terms to Queue"),(0,e.createElement)(r.Button,{isPrimary:!0,onClick:async()=>{$(!0),T(0);const e=E.length;let t=[],a=E.slice(),r={};for(;a.length>0;){const n=a.splice(0,10);n.forEach((e=>{e.parent&&r[e.parent]&&(console.log("Updating parent ID",e.parent,r[e.parent]),e.parent=r[e.parent])}));try{const{processedTerms:a,idMap:s}=await z(n);t=[...t,...a],r={...r,...s},T(Math.round(t.length/e*100)),m((e=>[...e,...n.map((e=>{const t=a.find((t=>t.old_id===e.term_id));return{...e,term_id:t.term_id,isNew:!1,parent:t.parent,slug:t.slug,description:t.description}}))])),w((e=>e.filter((e=>!n.includes(e)))))}catch(e){L({message:"Error generating terms. Please try again.",status:"error"})}}$(!1),L({message:`${t.length} terms successfully generated!`,status:"success"})},className:"generate-terms-button",disabled:0===E.length||k||P},"Generate Terms")),N&&(0,e.createElement)("div",{className:"edit-term-form"},(0,e.createElement)("h2",null,"Edit Term"),(0,e.createElement)(r.TextControl,{label:"Name",value:N.name,onChange:e=>_({...N,name:e})}),(0,e.createElement)(r.TextControl,{label:"Slug",value:N.slug,onChange:e=>_({...N,slug:e})}),(0,e.createElement)(r.TextControl,{label:"Description",value:N.description,onChange:e=>_({...N,description:e})}),(0,e.createElement)(r.Button,{isPrimary:!0,onClick:()=>{N.isNew?w(E.map((e=>e.term_id===N.term_id?N:e))):o()({path:`/wp/v2/${n}/${N.term_id}`,method:"POST",data:{name:N.name,slug:N.slug,description:N.description}}).then((()=>{m(c.map((e=>e.term_id===N.term_id?N:e)))})),L({message:`"${N.name}" successfully edited.`,status:"info"}),_(null)}},"Save"),(0,e.createElement)(r.Button,{onClick:()=>_(null)},"Cancel")),(0,e.createElement)("div",{className:"term-tree-container",ref:M},b?(0,e.createElement)(r.Spinner,null):(0,e.createElement)(e.Fragment,null,(0,e.createElement)("p",{className:"taxonomy-name"},t.find((e=>e.value===n)).label),U(F(G)))))):(0,e.createElement)(r.SelectControl,{label:"Select Taxonomy",value:n,options:t,onChange:e=>{l(e),O(e);const t=new URL(window.location);t.searchParams.set("taxonomy",e),window.history.pushState({taxonomy:e},"",t)},className:"taxonomy-select"}))};document.addEventListener("DOMContentLoaded",(()=>{const a=document.getElementById("bulk-term-generator-root");a&&(0,t.H)(a).render((0,e.createElement)(c,null))}))})()})();