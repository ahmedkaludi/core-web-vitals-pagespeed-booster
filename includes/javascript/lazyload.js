// class cwvLazyLoadScripts{constructor(e){this.triggerEvents=e,this.eventOptions={passive:!0},this.userEventListener=this.triggerListener.bind(this),this.delayedScripts={normal:[],async:[],defer:[]},this.allJQueries=[]}_addUserInteractionListener(e){this.triggerEvents.forEach((t=>window.addEventListener(t,e.userEventListener,e.eventOptions)))}_removeUserInteractionListener(e){this.triggerEvents.forEach((t=>window.removeEventListener(t,e.userEventListener,e.eventOptions)))}triggerListener(){this._removeUserInteractionListener(this),"loading"===document.readyState?document.addEventListener("DOMContentLoaded",this._loadEverythingNow.bind(this)):this._loadEverythingNow()}async _loadEverythingNow(){this._delayEventListeners(),this._delayJQueryReady(this),this._handleDocumentWrite(),this._registerAllDelayedScripts(),this._preloadAllScripts(),await this._triggerDOMContentLoaded(),await this._triggerWindowLoad(),window.dispatchEvent(new Event("cwv-allScriptsLoaded"))}_registerAllDelayedScripts(){document.querySelectorAll("script[type=cwvlazyloadscript]").forEach((e=>{e.hasAttribute("src")?e.hasAttribute("async")&&!1!==e.async?this.delayedScripts.async.push(e):e.hasAttribute("defer")&&!1!==e.defer||"module"===e.getAttribute("data-cwv-type")?this.delayedScripts.defer.push(e):this.delayedScripts.normal.push(e):this.delayedScripts.normal.push(e)}))}async _transformScript(e){return await this._requestAnimFrame(),new Promise((t=>{const n=document.createElement("script");let r;[...e.attributes].forEach((e=>{let t=e.nodeName;"type"!==t&&("data-cwv-type"===t&&(t="type",r=e.nodeValue),n.setAttribute(t,e.nodeValue))})),e.hasAttribute("src")?(n.addEventListener("load",t),n.addEventListener("error",t)):(n.text=e.text,t()),e.parentNode.replaceChild(n,e)}))}_preloadAllScripts(){var e=document.createDocumentFragment();[...this.delayedScripts.normal,...this.delayedScripts.defer,...this.delayedScripts.async].forEach((t=>{const n=t.getAttribute("src");if(n){const t=document.createElement("link");t.href=n,t.rel="preload",t.as="script",e.appendChild(t)}})),document.head.appendChild(e)}_delayEventListeners(){let e={};function t(t,n){!function(t){function n(n){return e[t].eventsToRewrite.indexOf(n)>=0?"cwv-"+n:n}e[t]||(e[t]={originalFunctions:{add:t.addEventListener,remove:t.removeEventListener},eventsToRewrite:[]},t.addEventListener=function(){arguments[0]=n(arguments[0]),e[t].originalFunctions.add.apply(t,arguments)},t.removeEventListener=function(){arguments[0]=n(arguments[0]),e[t].originalFunctions.remove.apply(t,arguments)})}(t),e[t].eventsToRewrite.push(n)}function n(e,t){let n=e[t];Object.defineProperty(e,t,{get:()=>n||function(){},set(r){e["cwv"+t]=n=r}})}t(document,"DOMContentLoaded"),t(window,"DOMContentLoaded"),t(window,"load"),t(window,"pageshow"),t(document,"readystatechange"),n(document,"onreadystatechange"),n(window,"onload"),n(window,"onpageshow")}_delayJQueryReady(e){let t=window.jQuery;Object.defineProperty(window,"jQuery",{get:()=>t,set(n){if(n&&n.fn&&!e.allJQueries.includes(n)){n.fn.ready=n.fn.init.prototype.ready=function(t){e.domReadyFired?t.bind(document)(n):document.addEventListener("cwv-DOMContentLoaded",(()=>t.bind(document)(n)))};const t=n.fn.on;n.fn.on=n.fn.init.prototype.on=function(){if(this[0]===window){function e(e){return e.split(" ").map((e=>"load"===e||0===e.indexOf("load.")?"cwv-jquery-load":e)).join(" ")}"string"==typeof arguments[0]||arguments[0]instanceof String?arguments[0]=e(arguments[0]):"object"==typeof arguments[0]&&Object.keys(arguments[0]).forEach((t=>{delete Object.assign(arguments[0],{[e(t)]:arguments[0][t]})[t]}))}return t.apply(this,arguments),this},e.allJQueries.push(n)}t=n}})}async _triggerDOMContentLoaded(){this.domReadyFired=!0,await this._requestAnimFrame(),document.dispatchEvent(new Event("cwv-DOMContentLoaded")),await this._requestAnimFrame(),window.dispatchEvent(new Event("cwv-DOMContentLoaded")),await this._requestAnimFrame(),document.dispatchEvent(new Event("cwv-readystatechange")),await this._requestAnimFrame(),document.cwvonreadystatechange&&document.cwvonreadystatechange()}async _triggerWindowLoad(){await this._requestAnimFrame(),window.dispatchEvent(new Event("cwv-load")),await this._requestAnimFrame(),window.cwvonload&&window.cwvonload(),await this._requestAnimFrame(),this.allJQueries.forEach((e=>e(window).trigger("cwv-jquery-load"))),window.dispatchEvent(new Event("cwv-pageshow")),await this._requestAnimFrame(),window.cwvonpageshow&&window.cwvonpageshow()}_handleDocumentWrite(){const e=new Map;document.write=document.writeln=function(t){const n=document.currentScript,r=document.createRange(),i=n.parentElement;let o=e.get(n);void 0===o&&(o=n.nextSibling,e.set(n,o));const a=document.createDocumentFragment();r.setStart(a,0),a.appendChild(r.createContextualFragment(t)),i.insertBefore(a,o)}}async _requestAnimFrame(){return new Promise((e=>requestAnimationFrame(e)))}static run(){const e=new cwvLazyLoadScripts(["keydown","touchmove","touchstart","touchend","wheel"]);e._addUserInteractionListener(e)}}cwvLazyLoadScripts.run();

var run=false;
class cwvLazyLoadScripts {
    constructor(e) {
        this.triggerEvents = e, this.eventOptions = {
            passive: !0
        }, this.userEventListener = this.triggerListener.bind(this), this.delayedScripts = {
            normal: [],
            async: [],
            defer: []
        }, this.allJQueries = [],
        this.firstrun=false;
    }
    _addUserInteractionListener(e) {
        this.triggerEvents.forEach((t => window.addEventListener(t, e.userEventListener, e.eventOptions)))
    }
    _removeUserInteractionListener(e) {
        this.triggerEvents.forEach((t => window.removeEventListener(t, e.userEventListener, e.eventOptions)))
    }
    triggerListener() {
        this.firstrun=true;
        this._removeUserInteractionListener(this), "loading" === document.readyState ? document.addEventListener("DOMContentLoaded", this._loadEverythingNow.bind(this)) : this._loadEverythingNow()
    }
    async _loadEverythingNow() {
        this.loadAllCss(),this._delayEventListeners(),  this._delayJQueryReady(this), this._handleDocumentWrite(), this._registerAllDelayedScripts(), this._preloadAllScripts(),this.loadAllJs(),await this._triggerDOMContentLoaded(), await this._triggerWindowLoad(), window.dispatchEvent(new Event("cwv-allScriptsLoaded"))
    }
    _registerAllDelayedScripts() {
        document.querySelectorAll("script[type=cwvlazyloadscript]").forEach((e => {
            e.hasAttribute("src") ? e.hasAttribute("async") && !1 !== e.async ? this.delayedScripts.async.push(e) : e.hasAttribute("defer") && !1 !== e.defer || "module" === e.getAttribute("data-cwv-type") ? this.delayedScripts.defer.push(e) : this.delayedScripts.normal.push(e) : this.delayedScripts.normal.push(e)
        }))
    }
    /*
    async _transformScript(e) {
        return await this._requestAnimFrame(), new Promise((t => {
            const n = document.createElement("script");
            let r;
            console.log('here it is');
            [...e.attributes].forEach((e => {
                let t = e.nodeName;
                "type" !== t && ("data-cwv-type" === t && (t = "type", r = e.nodeValue), n.setAttribute(t, e.nodeValue))
            })), e.hasAttribute("src") ? (n.addEventListener("load", t), n.addEventListener("error", t)) : (n.text = e.text, t()), e.parentNode.replaceChild(n, e)
        }))
    }
    */
    _preloadAllScripts() {
        var e = document.createDocumentFragment();
        [...this.delayedScripts.normal, ...this.delayedScripts.defer, ...this.delayedScripts.async].forEach((t => {
            const n = this.removeVersionFromLink(t.getAttribute("src"));
            if (n) {
                const t = document.createElement("link");
                t.href = n, t.rel = "preload", t.as = "script", e.appendChild(t),t.onload="this.rel="
            }
        })), document.head.appendChild(e)
    }
    _delayEventListeners() {
        let e = {};

        function t(t, n) {
            ! function(t) {
                function n(n) {
                    return e[t].eventsToRewrite.indexOf(n) >= 0 ? "cwv-" + n : n
                }
                e[t] || (e[t] = {
                    originalFunctions: {
                        add: t.addEventListener,
                        remove: t.removeEventListener
                    },
                    eventsToRewrite: []
                }, t.addEventListener = function() {
                    arguments[0] = n(arguments[0]), e[t].originalFunctions.add.apply(t, arguments)
                }, t.removeEventListener = function() {
                    arguments[0] = n(arguments[0]), e[t].originalFunctions.remove.apply(t, arguments)
                })
            }(t), e[t].eventsToRewrite.push(n)
        }

        function n(e, t) {
            let n = e[t];
            Object.defineProperty(e, t, {
                get: () => n || function() {},
                set(r) {
                    e["cwv" + t] = n = r
                }
            })
        }
        t(document, "DOMContentLoaded"), t(window, "DOMContentLoaded"), t(window, "load"), t(window, "pageshow"), t(document, "readystatechange"), n(document, "onreadystatechange"), n(window, "onload"), n(window, "onpageshow")
    }
    _delayJQueryReady(e) {
        let t = window.jQuery;
        Object.defineProperty(window, "jQuery", {
            get: () => t,
            set(n) {
                if (n && n.fn && !e.allJQueries.includes(n)) {
                    n.fn.ready = n.fn.init.prototype.ready = function(t) {
                        e.domReadyFired ? t.bind(document)(n) : document.addEventListener("cwv-DOMContentLoaded", (() => t.bind(document)(n)))
                    };
                    const t = n.fn.on;
                    n.fn.on = n.fn.init.prototype.on = function() {
                        if (this[0] === window) {
                            function e(e) {
                                return e.split(" ").map((e => "load" === e || 0 === e.indexOf("load.") ? "cwv-jquery-load" : e)).join(" ")
                            }
                            "string" == typeof arguments[0] || arguments[0] instanceof String ? arguments[0] = e(arguments[0]) : "object" == typeof arguments[0] && Object.keys(arguments[0]).forEach((t => {
                                delete Object.assign(arguments[0], {
                                    [e(t)]: arguments[0][t]
                                })[t]
                            }))
                        }
                        return t.apply(this, arguments), this
                    }, e.allJQueries.push(n)
                }
                t = n
            }
        })
    }
    async _triggerDOMContentLoaded() {
        this.domReadyFired = !0, await this._requestAnimFrame(), document.dispatchEvent(new Event("cwv-DOMContentLoaded")), await this._requestAnimFrame(), window.dispatchEvent(new Event("cwv-DOMContentLoaded")), await this._requestAnimFrame(), document.dispatchEvent(new Event("cwv-readystatechange")), await this._requestAnimFrame(), document.cwvonreadystatechange && document.cwvonreadystatechange()
        
    }
    async _triggerWindowLoad() {
        await this._requestAnimFrame(), window.dispatchEvent(new Event("cwv-load")), await this._requestAnimFrame(), window.cwvonload && window.cwvonload(), await this._requestAnimFrame(), this.allJQueries.forEach((e => e(window).trigger("cwv-jquery-load"))), window.dispatchEvent(new Event("cwv-pageshow")), await this._requestAnimFrame(), window.cwvonpageshow && window.cwvonpageshow()
    }
    _handleDocumentWrite() {
        const e = new Map;
        document.write = document.writeln = function(t) {
            const n = document.currentScript,
                r = document.createRange(),
                i = n.parentElement;
            let o = e.get(n);
            void 0 === o && (o = n.nextSibling, e.set(n, o));
            const a = document.createDocumentFragment();
            r.setStart(a, 0), a.appendChild(r.createContextualFragment(t)), i.insertBefore(a, o)
        }
    }
    async _requestAnimFrame() {
        return new Promise((e => requestAnimationFrame(e)))
    }
     loadAllCss(){
        var cssEle = document.querySelectorAll("link[rel=cwvpsbdelayedstyle]");
            for(var i=0; i <= cssEle.length;i++){
                if(cssEle[i]){
                    var cssMain = document.createElement("link");
                    cssMain.href = this.removeVersionFromLink(cssEle[i].href);
                    cssMain.rel = "stylesheet";
                    cssMain.type = "text/css";
                    document.getElementsByTagName("head")[0].appendChild(cssMain);
                }
            }
            
            
            var cssEle = document.querySelectorAll("style[type=cwvpsbdelayedstyle]");
            for(var i=0; i <= cssEle.length;i++){
                if(cssEle[i]){
                    var cssMain = document.createElement("style");
                    cssMain.type = "text/css";
                    cssMain.textContent = cssEle[i].textContent;
                    document.getElementsByTagName("head")[0].appendChild(cssMain);
                }
            }
        }
        
        loadAllJs(){
        
            var scriptEle = document.querySelectorAll("script[type=cwvlazyloadscript]");
            var jqueryScript = document.querySelector("script[id=jquery-core-js]");
            var jqueryMigrateScript = document.querySelector("script[id=jquery-migrate-js]");
            if(jqueryScript)
            {
                var scriptMain = document.createElement("script");
                scriptMain.type = "text/javascript";
                if(jqueryScript.src)
                {   
                    scriptMain.src = this.removeVersionFromLink(jqueryScript.src);
                    document.head.appendChild(scriptMain);
                }
            }
            if(jqueryMigrateScript)
            {
                var scriptMain = document.createElement("script");
                scriptMain.type = "text/javascript";
                if(jqueryMigrateScript.src)
                {   
                    scriptMain.src = this.removeVersionFromLink(jqueryMigrateScript.src);
                    document.head.appendChild(scriptMain);
                }
            }

            for(var i=0; i <= scriptEle.length;i++){
                if(scriptEle[i] && scriptEle[i].id !='jquery-core-js' && scriptEle[i].id !='jquery-migrate-js'){
                    var scriptMain = document.createElement("script");
                    scriptMain.type = "text/javascript";
                    if(scriptEle[i].src)
                    {scriptMain.src = this.removeVersionFromLink(scriptEle[i].src);}
                    scriptMain.textContent = scriptEle[i].textContent;
                    document.body.appendChild(scriptMain);
                    
                }
            }
            }
           
          
            removeVersionFromLink(link)
            {
                if(!link)
                { return '';}
                const url = new URL(link);
                url.searchParams.delete('ver');
                return url.href;
            }

           
    static run() {
        const e = new cwvLazyLoadScripts(["keydown","mouseover","mousemove", "touchmove", "touchstart", "touchend", "wheel","DOMContentLoaded"]);
        e._addUserInteractionListener(e)
    }
}
cwvLazyLoadScripts.run();

