import e from"./utility.js";class g{constructor(){const o=this,t=document.getElementById("endpoint-search"),n=document.getElementById("endpoint-search-icon-search"),c=document.getElementById("endpoint-search-icon-close");e.toggleElement(c,"none"),t.addEventListener("keyup",function(){o.processSearch()}),c.addEventListener("click",function(s){t.value="",e.toggleElement(n,"block"),e.toggleElement(c,"none"),o.processSearch()})}processSearch(){const o=document.getElementById("endpoint-search"),t=document.getElementById("endpoint-search-icon-search"),n=document.getElementById("endpoint-search-icon-close");if(!o||!t||!n)return;const c=document.querySelectorAll(".request"),s=o.value.toLowerCase();s===""?(e.toggleElement(t,"block"),e.toggleElement(n,"none")):(e.toggleElement(t,"none"),e.toggleElement(n,"block")),c.forEach(l=>{const r=l.getAttribute("data-method").toLowerCase(),a=l.getAttribute("data-endpoint").toLowerCase(),d=l.getAttribute("data-tag").toLowerCase();r.match(s)||a.match(s)||d.match(s)?e.toggleElement(l,"block"):e.toggleElement(l,"none")})}}export default new g;
