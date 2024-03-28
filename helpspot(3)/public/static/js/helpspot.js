/*  Prototype JavaScript framework, version 1.7
 *  (c) 2005-2010 Sam Stephenson
 *
 *  Prototype is freely distributable under the terms of an MIT-style license.
 *  For details, see the Prototype web site: http://www.prototypejs.org/
 *
 *--------------------------------------------------------------------------*/

var Prototype = {

  Version: '1.7',

  Browser: (function(){
    var ua = navigator.userAgent;
    var isOpera = Object.prototype.toString.call(window.opera) == '[object Opera]';
    return {
      IE:             !!window.attachEvent && !isOpera,
      Opera:          isOpera,
      WebKit:         ua.indexOf('AppleWebKit/') > -1,
      Gecko:          ua.indexOf('Gecko') > -1 && ua.indexOf('KHTML') === -1,
      MobileSafari:   /Apple.*Mobile/.test(ua)
    }
  })(),

  BrowserFeatures: {
    XPath: !!document.evaluate,

    SelectorsAPI: !!document.querySelector,

    ElementExtensions: (function() {
      var constructor = window.Element || window.HTMLElement;
      return !!(constructor && constructor.prototype);
    })(),
    SpecificElementExtensions: (function() {
      if (typeof window.HTMLDivElement !== 'undefined')
        return true;

      var div = document.createElement('div'),
          form = document.createElement('form'),
          isSupported = false;

      if (div['__proto__'] && (div['__proto__'] !== form['__proto__'])) {
        isSupported = true;
      }

      div = form = null;

      return isSupported;
    })()
  },

  ScriptFragment: '<script[^>]*>([\\S\\s]*?)<\/script>',
  JSONFilter: /^\/\*-secure-([\s\S]*)\*\/\s*$/,

  emptyFunction: function() { },

  K: function(x) { return x }
};

if (Prototype.Browser.MobileSafari)
  Prototype.BrowserFeatures.SpecificElementExtensions = false;
/* Based on Alex Arnell's inheritance implementation. */

var Class = (function() {

  var IS_DONTENUM_BUGGY = (function(){
    for (var p in { toString: 1 }) {
      if (p === 'toString') return false;
    }
    return true;
  })();

  function subclass() {};
  function create() {
    var parent = null, properties = $A(arguments);
    if (Object.isFunction(properties[0]))
      parent = properties.shift();

    function klass() {
      this.initialize.apply(this, arguments);
    }

    Object.extend(klass, Class.Methods);
    klass.superclass = parent;
    klass.subclasses = [];

    if (parent) {
      subclass.prototype = parent.prototype;
      klass.prototype = new subclass;
      parent.subclasses.push(klass);
    }

    for (var i = 0, length = properties.length; i < length; i++)
      klass.addMethods(properties[i]);

    if (!klass.prototype.initialize)
      klass.prototype.initialize = Prototype.emptyFunction;

    klass.prototype.constructor = klass;
    return klass;
  }

  function addMethods(source) {
    var ancestor   = this.superclass && this.superclass.prototype,
        properties = Object.keys(source);

    if (IS_DONTENUM_BUGGY) {
      if (source.toString != Object.prototype.toString)
        properties.push("toString");
      if (source.valueOf != Object.prototype.valueOf)
        properties.push("valueOf");
    }

    for (var i = 0, length = properties.length; i < length; i++) {
      var property = properties[i], value = source[property];
      if (ancestor && Object.isFunction(value) &&
          value.argumentNames()[0] == "$super") {
        var method = value;
        value = (function(m) {
          return function() { return ancestor[m].apply(this, arguments); };
        })(property).wrap(method);

        value.valueOf = method.valueOf.bind(method);
        value.toString = method.toString.bind(method);
      }
      this.prototype[property] = value;
    }

    return this;
  }

  return {
    create: create,
    Methods: {
      addMethods: addMethods
    }
  };
})();
(function() {

  var _toString = Object.prototype.toString,
      NULL_TYPE = 'Null',
      UNDEFINED_TYPE = 'Undefined',
      BOOLEAN_TYPE = 'Boolean',
      NUMBER_TYPE = 'Number',
      STRING_TYPE = 'String',
      OBJECT_TYPE = 'Object',
      FUNCTION_CLASS = '[object Function]',
      BOOLEAN_CLASS = '[object Boolean]',
      NUMBER_CLASS = '[object Number]',
      STRING_CLASS = '[object String]',
      ARRAY_CLASS = '[object Array]',
      DATE_CLASS = '[object Date]',
      NATIVE_JSON_STRINGIFY_SUPPORT = window.JSON &&
        typeof JSON.stringify === 'function' &&
        JSON.stringify(0) === '0' &&
        typeof JSON.stringify(Prototype.K) === 'undefined';

  function Type(o) {
    switch(o) {
      case null: return NULL_TYPE;
      case (void 0): return UNDEFINED_TYPE;
    }
    var type = typeof o;
    switch(type) {
      case 'boolean': return BOOLEAN_TYPE;
      case 'number':  return NUMBER_TYPE;
      case 'string':  return STRING_TYPE;
    }
    return OBJECT_TYPE;
  }

  function extend(destination, source) {
    for (var property in source)
      destination[property] = source[property];
    return destination;
  }

  function inspect(object) {
    try {
      if (isUndefined(object)) return 'undefined';
      if (object === null) return 'null';
      return object.inspect ? object.inspect() : String(object);
    } catch (e) {
      if (e instanceof RangeError) return '...';
      throw e;
    }
  }

  function toJSON(value) {
    return Str('', { '': value }, []);
  }

  function Str(key, holder, stack) {
    var value = holder[key],
        type = typeof value;

    if (Type(value) === OBJECT_TYPE && typeof value.toJSON === 'function') {
      value = value.toJSON(key);
    }

    var _class = _toString.call(value);

    switch (_class) {
      case NUMBER_CLASS:
      case BOOLEAN_CLASS:
      case STRING_CLASS:
        value = value.valueOf();
    }

    switch (value) {
      case null: return 'null';
      case true: return 'true';
      case false: return 'false';
    }

    type = typeof value;
    switch (type) {
      case 'string':
        return value.inspect(true);
      case 'number':
        return isFinite(value) ? String(value) : 'null';
      case 'object':

        for (var i = 0, length = stack.length; i < length; i++) {
          if (stack[i] === value) { throw new TypeError(); }
        }
        stack.push(value);

        var partial = [];
        if (_class === ARRAY_CLASS) {
          for (var i = 0, length = value.length; i < length; i++) {
            var str = Str(i, value, stack);
            partial.push(typeof str === 'undefined' ? 'null' : str);
          }
          partial = '[' + partial.join(',') + ']';
        } else {
          var keys = Object.keys(value);
          for (var i = 0, length = keys.length; i < length; i++) {
            var key = keys[i], str = Str(key, value, stack);
            if (typeof str !== "undefined") {
               partial.push(key.inspect(true)+ ':' + str);
             }
          }
          partial = '{' + partial.join(',') + '}';
        }
        stack.pop();
        return partial;
    }
  }

  function stringify(object) {
    return JSON.stringify(object);
  }

  function toQueryString(object) {
    return $H(object).toQueryString();
  }

  function toHTML(object) {
    return object && object.toHTML ? object.toHTML() : String.interpret(object);
  }

  function keys(object) {
    if (Type(object) !== OBJECT_TYPE) { throw new TypeError(); }
    var results = [];
    for (var property in object) {
      if (object.hasOwnProperty(property)) {
        results.push(property);
      }
    }
    return results;
  }

  function values(object) {
    var results = [];
    for (var property in object)
      results.push(object[property]);
    return results;
  }

  function clone(object) {
    return extend({ }, object);
  }

  function isElement(object) {
    return !!(object && object.nodeType == 1);
  }

  function isArray(object) {
    return _toString.call(object) === ARRAY_CLASS;
  }

  var hasNativeIsArray = (typeof Array.isArray == 'function')
    && Array.isArray([]) && !Array.isArray({});

  if (hasNativeIsArray) {
    isArray = Array.isArray;
  }

  function isHash(object) {
    return object instanceof Hash;
  }

  function isFunction(object) {
    return _toString.call(object) === FUNCTION_CLASS;
  }

  function isString(object) {
    return _toString.call(object) === STRING_CLASS;
  }

  function isNumber(object) {
    return _toString.call(object) === NUMBER_CLASS;
  }

  function isDate(object) {
    return _toString.call(object) === DATE_CLASS;
  }

  function isUndefined(object) {
    return typeof object === "undefined";
  }

  extend(Object, {
    extend:        extend,
    inspect:       inspect,
    toJSON:        NATIVE_JSON_STRINGIFY_SUPPORT ? stringify : toJSON,
    toQueryString: toQueryString,
    toHTML:        toHTML,
    keys:          Object.keys || keys,
    values:        values,
    clone:         clone,
    isElement:     isElement,
    isArray:       isArray,
    isHash:        isHash,
    isFunction:    isFunction,
    isString:      isString,
    isNumber:      isNumber,
    isDate:        isDate,
    isUndefined:   isUndefined
  });
})();
Object.extend(Function.prototype, (function() {
  var slice = Array.prototype.slice;

  function update(array, args) {
    var arrayLength = array.length, length = args.length;
    while (length--) array[arrayLength + length] = args[length];
    return array;
  }

  function merge(array, args) {
    array = slice.call(array, 0);
    return update(array, args);
  }

  function argumentNames() {
    var names = this.toString().match(/^[\s\(]*function[^(]*\(([^)]*)\)/)[1]
      .replace(/\/\/.*?[\r\n]|\/\*(?:.|[\r\n])*?\*\//g, '')
      .replace(/\s+/g, '').split(',');
    return names.length == 1 && !names[0] ? [] : names;
  }

  function bind(context) {
    if (arguments.length < 2 && Object.isUndefined(arguments[0])) return this;
    var __method = this, args = slice.call(arguments, 1);
    return function() {
      var a = merge(args, arguments);
      return __method.apply(context, a);
    }
  }

  function bindAsEventListener(context) {
    var __method = this, args = slice.call(arguments, 1);
    return function(event) {
      var a = update([event || window.event], args);
      return __method.apply(context, a);
    }
  }

  function curry() {
    if (!arguments.length) return this;
    var __method = this, args = slice.call(arguments, 0);
    return function() {
      var a = merge(args, arguments);
      return __method.apply(this, a);
    }
  }

  function delay(timeout) {
    var __method = this, args = slice.call(arguments, 1);
    timeout = timeout * 1000;
    return window.setTimeout(function() {
      return __method.apply(__method, args);
    }, timeout);
  }

  function defer() {
    var args = update([0.01], arguments);
    return this.delay.apply(this, args);
  }

  function wrap(wrapper) {
    var __method = this;
    return function() {
      var a = update([__method.bind(this)], arguments);
      return wrapper.apply(this, a);
    }
  }

  function methodize() {
    if (this._methodized) return this._methodized;
    var __method = this;
    return this._methodized = function() {
      var a = update([this], arguments);
      return __method.apply(null, a);
    };
  }

  return {
    argumentNames:       argumentNames,
    bind:                bind,
    bindAsEventListener: bindAsEventListener,
    curry:               curry,
    delay:               delay,
    defer:               defer,
    wrap:                wrap,
    methodize:           methodize
  }
})());



(function(proto) {


  function toISOString() {
    return this.getUTCFullYear() + '-' +
      (this.getUTCMonth() + 1).toPaddedString(2) + '-' +
      this.getUTCDate().toPaddedString(2) + 'T' +
      this.getUTCHours().toPaddedString(2) + ':' +
      this.getUTCMinutes().toPaddedString(2) + ':' +
      this.getUTCSeconds().toPaddedString(2) + 'Z';
  }


  function toJSON() {
    return this.toISOString();
  }

  if (!proto.toISOString) proto.toISOString = toISOString;
  if (!proto.toJSON) proto.toJSON = toJSON;

})(Date.prototype);


RegExp.prototype.match = RegExp.prototype.test;

RegExp.escape = function(str) {
  return String(str).replace(/([.*+?^=!:${}()|[\]\/\\])/g, '\\$1');
};
var PeriodicalExecuter = Class.create({
  initialize: function(callback, frequency) {
    this.callback = callback;
    this.frequency = frequency;
    this.currentlyExecuting = false;

    this.registerCallback();
  },

  registerCallback: function() {
    this.timer = setInterval(this.onTimerEvent.bind(this), this.frequency * 1000);
  },

  execute: function() {
    this.callback(this);
  },

  stop: function() {
    if (!this.timer) return;
    clearInterval(this.timer);
    this.timer = null;
  },

  onTimerEvent: function() {
    if (!this.currentlyExecuting) {
      try {
        this.currentlyExecuting = true;
        this.execute();
        this.currentlyExecuting = false;
      } catch(e) {
        this.currentlyExecuting = false;
        throw e;
      }
    }
  }
});
Object.extend(String, {
  interpret: function(value) {
    return value == null ? '' : String(value);
  },
  specialChar: {
    '\b': '\\b',
    '\t': '\\t',
    '\n': '\\n',
    '\f': '\\f',
    '\r': '\\r',
    '\\': '\\\\'
  }
});

Object.extend(String.prototype, (function() {
  var NATIVE_JSON_PARSE_SUPPORT = window.JSON &&
    typeof JSON.parse === 'function' &&
    JSON.parse('{"test": true}').test;

  function prepareReplacement(replacement) {
    if (Object.isFunction(replacement)) return replacement;
    var template = new Template(replacement);
    return function(match) { return template.evaluate(match) };
  }

  function gsub(pattern, replacement) {
    var result = '', source = this, match;
    replacement = prepareReplacement(replacement);

    if (Object.isString(pattern))
      pattern = RegExp.escape(pattern);

    if (!(pattern.length || pattern.source)) {
      replacement = replacement('');
      return replacement + source.split('').join(replacement) + replacement;
    }

    while (source.length > 0) {
      if (match = source.match(pattern)) {
        result += source.slice(0, match.index);
        result += String.interpret(replacement(match));
        source  = source.slice(match.index + match[0].length);
      } else {
        result += source, source = '';
      }
    }
    return result;
  }

  function sub(pattern, replacement, count) {
    replacement = prepareReplacement(replacement);
    count = Object.isUndefined(count) ? 1 : count;

    return this.gsub(pattern, function(match) {
      if (--count < 0) return match[0];
      return replacement(match);
    });
  }

  function scan(pattern, iterator) {
    this.gsub(pattern, iterator);
    return String(this);
  }

  function truncate(length, truncation) {
    length = length || 30;
    truncation = Object.isUndefined(truncation) ? '...' : truncation;
    return this.length > length ?
      this.slice(0, length - truncation.length) + truncation : String(this);
  }

  function strip() {
    return this.replace(/^\s+/, '').replace(/\s+$/, '');
  }

  function stripTags() {
    return this.replace(/<\w+(\s+("[^"]*"|'[^']*'|[^>])+)?>|<\/\w+>/gi, '');
  }

  function stripScripts() {
    return this.replace(new RegExp(Prototype.ScriptFragment, 'img'), '');
  }

  function extractScripts() {
    var matchAll = new RegExp(Prototype.ScriptFragment, 'img'),
        matchOne = new RegExp(Prototype.ScriptFragment, 'im');
    return (this.match(matchAll) || []).map(function(scriptTag) {
      return (scriptTag.match(matchOne) || ['', ''])[1];
    });
  }

  function evalScripts() {
    return this.extractScripts().map(function(script) { return eval(script) });
  }

  function escapeHTML() {
    return this.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  function unescapeHTML() {
    return this.stripTags().replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&amp;/g,'&');
  }


  function toQueryParams(separator) {
    var match = this.strip().match(/([^?#]*)(#.*)?$/);
    if (!match) return { };

    return match[1].split(separator || '&').inject({ }, function(hash, pair) {
      if ((pair = pair.split('='))[0]) {
        var key = decodeURIComponent(pair.shift()),
            value = pair.length > 1 ? pair.join('=') : pair[0];

        if (value != undefined) value = decodeURIComponent(value);

        if (key in hash) {
          if (!Object.isArray(hash[key])) hash[key] = [hash[key]];
          hash[key].push(value);
        }
        else hash[key] = value;
      }
      return hash;
    });
  }

  function toArray() {
    return this.split('');
  }

  function succ() {
    return this.slice(0, this.length - 1) +
      String.fromCharCode(this.charCodeAt(this.length - 1) + 1);
  }

  function times(count) {
    return count < 1 ? '' : new Array(count + 1).join(this);
  }

  function camelize() {
    return this.replace(/-+(.)?/g, function(match, chr) {
      return chr ? chr.toUpperCase() : '';
    });
  }

  function capitalize() {
    return this.charAt(0).toUpperCase() + this.substring(1).toLowerCase();
  }

  function underscore() {
    return this.replace(/::/g, '/')
               .replace(/([A-Z]+)([A-Z][a-z])/g, '$1_$2')
               .replace(/([a-z\d])([A-Z])/g, '$1_$2')
               .replace(/-/g, '_')
               .toLowerCase();
  }

  function dasherize() {
    return this.replace(/_/g, '-');
  }

  function inspect(useDoubleQuotes) {
    var escapedString = this.replace(/[\x00-\x1f\\]/g, function(character) {
      if (character in String.specialChar) {
        return String.specialChar[character];
      }
      return '\\u00' + character.charCodeAt().toPaddedString(2, 16);
    });
    if (useDoubleQuotes) return '"' + escapedString.replace(/"/g, '\\"') + '"';
    return "'" + escapedString.replace(/'/g, '\\\'') + "'";
  }

  function unfilterJSON(filter) {
    return this.replace(filter || Prototype.JSONFilter, '$1');
  }

  function isJSON() {
    var str = this;
    if (str.blank()) return false;
    str = str.replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g, '@');
    str = str.replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']');
    str = str.replace(/(?:^|:|,)(?:\s*\[)+/g, '');
    return (/^[\],:{}\s]*$/).test(str);
  }

  function evalJSON(sanitize) {
    var json = this.unfilterJSON(),
        cx = /[\u0000\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g;
    if (cx.test(json)) {
      json = json.replace(cx, function (a) {
        return '\\u' + ('0000' + a.charCodeAt(0).toString(16)).slice(-4);
      });
    }
    try {
      if (!sanitize || json.isJSON()) return eval('(' + json + ')');
    } catch (e) { }
    throw new SyntaxError('Badly formed JSON string: ' + this.inspect());
  }

  function parseJSON() {
    var json = this.unfilterJSON();
    return JSON.parse(json);
  }

  function include(pattern) {
    return this.indexOf(pattern) > -1;
  }

  function startsWith(pattern) {
    return this.lastIndexOf(pattern, 0) === 0;
  }

  function endsWith(pattern) {
    var d = this.length - pattern.length;
    return d >= 0 && this.indexOf(pattern, d) === d;
  }

  function empty() {
    return this == '';
  }

  function blank() {
    return /^\s*$/.test(this);
  }

  function interpolate(object, pattern) {
    return new Template(this, pattern).evaluate(object);
  }

  return {
    gsub:           gsub,
    sub:            sub,
    scan:           scan,
    truncate:       truncate,
    strip:          String.prototype.trim || strip,
    stripTags:      stripTags,
    stripScripts:   stripScripts,
    extractScripts: extractScripts,
    evalScripts:    evalScripts,
    escapeHTML:     escapeHTML,
    unescapeHTML:   unescapeHTML,
    toQueryParams:  toQueryParams,
    parseQuery:     toQueryParams,
    toArray:        toArray,
    succ:           succ,
    times:          times,
    camelize:       camelize,
    capitalize:     capitalize,
    underscore:     underscore,
    dasherize:      dasherize,
    inspect:        inspect,
    unfilterJSON:   unfilterJSON,
    isJSON:         isJSON,
    evalJSON:       NATIVE_JSON_PARSE_SUPPORT ? parseJSON : evalJSON,
    include:        include,
    startsWith:     startsWith,
    endsWith:       endsWith,
    empty:          empty,
    blank:          blank,
    interpolate:    interpolate
  };
})());

var Template = Class.create({
  initialize: function(template, pattern) {
    this.template = template.toString();
    this.pattern = pattern || Template.Pattern;
  },

  evaluate: function(object) {
    if (object && Object.isFunction(object.toTemplateReplacements))
      object = object.toTemplateReplacements();

    return this.template.gsub(this.pattern, function(match) {
      if (object == null) return (match[1] + '');

      var before = match[1] || '';
      if (before == '\\') return match[2];

      var ctx = object, expr = match[3],
          pattern = /^([^.[]+|\[((?:.*?[^\\])?)\])(\.|\[|$)/;

      match = pattern.exec(expr);
      if (match == null) return before;

      while (match != null) {
        var comp = match[1].startsWith('[') ? match[2].replace(/\\\\]/g, ']') : match[1];
        ctx = ctx[comp];
        if (null == ctx || '' == match[3]) break;
        expr = expr.substring('[' == match[3] ? match[1].length : match[0].length);
        match = pattern.exec(expr);
      }

      return before + String.interpret(ctx);
    });
  }
});
Template.Pattern = /(^|.|\r|\n)(#\{(.*?)\})/;

var $break = { };

var Enumerable = (function() {
  function each(iterator, context) {
    var index = 0;
    try {
      this._each(function(value) {
        iterator.call(context, value, index++);
      });
    } catch (e) {
      if (e != $break) throw e;
    }
    return this;
  }

  function eachSlice(number, iterator, context) {
    var index = -number, slices = [], array = this.toArray();
    if (number < 1) return array;
    while ((index += number) < array.length)
      slices.push(array.slice(index, index+number));
    return slices.collect(iterator, context);
  }

  function all(iterator, context) {
    iterator = iterator || Prototype.K;
    var result = true;
    this.each(function(value, index) {
      result = result && !!iterator.call(context, value, index);
      if (!result) throw $break;
    });
    return result;
  }

  function any(iterator, context) {
    iterator = iterator || Prototype.K;
    var result = false;
    this.each(function(value, index) {
      if (result = !!iterator.call(context, value, index))
        throw $break;
    });
    return result;
  }

  function collect(iterator, context) {
    iterator = iterator || Prototype.K;
    var results = [];
    this.each(function(value, index) {
      results.push(iterator.call(context, value, index));
    });
    return results;
  }

  function detect(iterator, context) {
    var result;
    this.each(function(value, index) {
      if (iterator.call(context, value, index)) {
        result = value;
        throw $break;
      }
    });
    return result;
  }

  function findAll(iterator, context) {
    var results = [];
    this.each(function(value, index) {
      if (iterator.call(context, value, index))
        results.push(value);
    });
    return results;
  }

  function grep(filter, iterator, context) {
    iterator = iterator || Prototype.K;
    var results = [];

    if (Object.isString(filter))
      filter = new RegExp(RegExp.escape(filter));

    this.each(function(value, index) {
      if (filter.match(value))
        results.push(iterator.call(context, value, index));
    });
    return results;
  }

  function include(object) {
    if (Object.isFunction(this.indexOf))
      if (this.indexOf(object) != -1) return true;

    var found = false;
    this.each(function(value) {
      if (value == object) {
        found = true;
        throw $break;
      }
    });
    return found;
  }

  function inGroupsOf(number, fillWith) {
    fillWith = Object.isUndefined(fillWith) ? null : fillWith;
    return this.eachSlice(number, function(slice) {
      while(slice.length < number) slice.push(fillWith);
      return slice;
    });
  }

  function inject(memo, iterator, context) {
    this.each(function(value, index) {
      memo = iterator.call(context, memo, value, index);
    });
    return memo;
  }

  function invoke(method) {
    var args = $A(arguments).slice(1);
    return this.map(function(value) {
      return value[method].apply(value, args);
    });
  }

  function max(iterator, context) {
    iterator = iterator || Prototype.K;
    var result;
    this.each(function(value, index) {
      value = iterator.call(context, value, index);
      if (result == null || value >= result)
        result = value;
    });
    return result;
  }

  function min(iterator, context) {
    iterator = iterator || Prototype.K;
    var result;
    this.each(function(value, index) {
      value = iterator.call(context, value, index);
      if (result == null || value < result)
        result = value;
    });
    return result;
  }

  function partition(iterator, context) {
    iterator = iterator || Prototype.K;
    var trues = [], falses = [];
    this.each(function(value, index) {
      (iterator.call(context, value, index) ?
        trues : falses).push(value);
    });
    return [trues, falses];
  }

  function pluck(property) {
    var results = [];
    this.each(function(value) {
      results.push(value[property]);
    });
    return results;
  }

  function reject(iterator, context) {
    var results = [];
    this.each(function(value, index) {
      if (!iterator.call(context, value, index))
        results.push(value);
    });
    return results;
  }

  function sortBy(iterator, context) {
    return this.map(function(value, index) {
      return {
        value: value,
        criteria: iterator.call(context, value, index)
      };
    }).sort(function(left, right) {
      var a = left.criteria, b = right.criteria;
      return a < b ? -1 : a > b ? 1 : 0;
    }).pluck('value');
  }

  function toArray() {
    return this.map();
  }

  function zip() {
    var iterator = Prototype.K, args = $A(arguments);
    if (Object.isFunction(args.last()))
      iterator = args.pop();

    var collections = [this].concat(args).map($A);
    return this.map(function(value, index) {
      return iterator(collections.pluck(index));
    });
  }

  function size() {
    return this.toArray().length;
  }

  function inspect() {
    return '#<Enumerable:' + this.toArray().inspect() + '>';
  }









  return {
    each:       each,
    eachSlice:  eachSlice,
    all:        all,
    every:      all,
    any:        any,
    some:       any,
    collect:    collect,
    map:        collect,
    detect:     detect,
    findAll:    findAll,
    select:     findAll,
    filter:     findAll,
    grep:       grep,
    include:    include,
    member:     include,
    inGroupsOf: inGroupsOf,
    inject:     inject,
    invoke:     invoke,
    max:        max,
    min:        min,
    partition:  partition,
    pluck:      pluck,
    reject:     reject,
    sortBy:     sortBy,
    toArray:    toArray,
    entries:    toArray,
    zip:        zip,
    size:       size,
    inspect:    inspect,
    find:       detect
  };
})();

function $A(iterable) {
  if (!iterable) return [];
  if ('toArray' in Object(iterable)) return iterable.toArray();
  var length = iterable.length || 0, results = new Array(length);
  while (length--) results[length] = iterable[length];
  return results;
}


function $w(string) {
  if (!Object.isString(string)) return [];
  string = string.strip();
  return string ? string.split(/\s+/) : [];
}

Array.from = $A;


(function() {
  var arrayProto = Array.prototype,
      slice = arrayProto.slice,
      _each = arrayProto.forEach; // use native browser JS 1.6 implementation if available

  function each(iterator, context) {
    for (var i = 0, length = this.length >>> 0; i < length; i++) {
      if (i in this) iterator.call(context, this[i], i, this);
    }
  }
  if (!_each) _each = each;

  function clear() {
    this.length = 0;
    return this;
  }

  function first() {
    return this[0];
  }

  function last() {
    return this[this.length - 1];
  }

  function compact() {
    return this.select(function(value) {
      return value != null;
    });
  }

  function flatten() {
    return this.inject([], function(array, value) {
      if (Object.isArray(value))
        return array.concat(value.flatten());
      array.push(value);
      return array;
    });
  }

  function without() {
    var values = slice.call(arguments, 0);
    return this.select(function(value) {
      return !values.include(value);
    });
  }

  function reverse(inline) {
    return (inline === false ? this.toArray() : this)._reverse();
  }

  function uniq(sorted) {
    return this.inject([], function(array, value, index) {
      if (0 == index || (sorted ? array.last() != value : !array.include(value)))
        array.push(value);
      return array;
    });
  }

  function intersect(array) {
    return this.uniq().findAll(function(item) {
      return array.detect(function(value) { return item === value });
    });
  }


  function clone() {
    return slice.call(this, 0);
  }

  function size() {
    return this.length;
  }

  function inspect() {
    return '[' + this.map(Object.inspect).join(', ') + ']';
  }

  function indexOf(item, i) {
    i || (i = 0);
    var length = this.length;
    if (i < 0) i = length + i;
    for (; i < length; i++)
      if (this[i] === item) return i;
    return -1;
  }

  function lastIndexOf(item, i) {
    i = isNaN(i) ? this.length : (i < 0 ? this.length + i : i) + 1;
    var n = this.slice(0, i).reverse().indexOf(item);
    return (n < 0) ? n : i - n - 1;
  }

  function concat() {
    var array = slice.call(this, 0), item;
    for (var i = 0, length = arguments.length; i < length; i++) {
      item = arguments[i];
      if (Object.isArray(item) && !('callee' in item)) {
        for (var j = 0, arrayLength = item.length; j < arrayLength; j++)
          array.push(item[j]);
      } else {
        array.push(item);
      }
    }
    return array;
  }

  Object.extend(arrayProto, Enumerable);

  if (!arrayProto._reverse)
    arrayProto._reverse = arrayProto.reverse;

  Object.extend(arrayProto, {
    _each:     _each,
    clear:     clear,
    first:     first,
    last:      last,
    compact:   compact,
    flatten:   flatten,
    without:   without,
    reverse:   reverse,
    uniq:      uniq,
    intersect: intersect,
    clone:     clone,
    toArray:   clone,
    size:      size,
    inspect:   inspect
  });

  var CONCAT_ARGUMENTS_BUGGY = (function() {
    return [].concat(arguments)[0][0] !== 1;
  })(1,2)

  if (CONCAT_ARGUMENTS_BUGGY) arrayProto.concat = concat;

  if (!arrayProto.indexOf) arrayProto.indexOf = indexOf;
  if (!arrayProto.lastIndexOf) arrayProto.lastIndexOf = lastIndexOf;
})();
function $H(object) {
  return new Hash(object);
};

var Hash = Class.create(Enumerable, (function() {
  function initialize(object) {
    this._object = Object.isHash(object) ? object.toObject() : Object.clone(object);
  }


  function _each(iterator) {
    for (var key in this._object) {
      var value = this._object[key], pair = [key, value];
      pair.key = key;
      pair.value = value;
      iterator(pair);
    }
  }

  function set(key, value) {
    return this._object[key] = value;
  }

  function get(key) {
    if (this._object[key] !== Object.prototype[key])
      return this._object[key];
  }

  function unset(key) {
    var value = this._object[key];
    delete this._object[key];
    return value;
  }

  function toObject() {
    return Object.clone(this._object);
  }



  function keys() {
    return this.pluck('key');
  }

  function values() {
    return this.pluck('value');
  }

  function index(value) {
    var match = this.detect(function(pair) {
      return pair.value === value;
    });
    return match && match.key;
  }

  function merge(object) {
    return this.clone().update(object);
  }

  function update(object) {
    return new Hash(object).inject(this, function(result, pair) {
      result.set(pair.key, pair.value);
      return result;
    });
  }

  function toQueryPair(key, value) {
    if (Object.isUndefined(value)) return key;
    return key + '=' + encodeURIComponent(String.interpret(value));
  }

  function toQueryString() {
    return this.inject([], function(results, pair) {
      var key = encodeURIComponent(pair.key), values = pair.value;

      if (values && typeof values == 'object') {
        if (Object.isArray(values)) {
          var queryValues = [];
          for (var i = 0, len = values.length, value; i < len; i++) {
            value = values[i];
            queryValues.push(toQueryPair(key, value));
          }
          return results.concat(queryValues);
        }
      } else results.push(toQueryPair(key, values));
      return results;
    }).join('&');
  }

  function inspect() {
    return '#<Hash:{' + this.map(function(pair) {
      return pair.map(Object.inspect).join(': ');
    }).join(', ') + '}>';
  }

  function clone() {
    return new Hash(this);
  }

  return {
    initialize:             initialize,
    _each:                  _each,
    set:                    set,
    get:                    get,
    unset:                  unset,
    toObject:               toObject,
    toTemplateReplacements: toObject,
    keys:                   keys,
    values:                 values,
    index:                  index,
    merge:                  merge,
    update:                 update,
    toQueryString:          toQueryString,
    inspect:                inspect,
    toJSON:                 toObject,
    clone:                  clone
  };
})());

Hash.from = $H;
Object.extend(Number.prototype, (function() {
  function toColorPart() {
    return this.toPaddedString(2, 16);
  }

  function succ() {
    return this + 1;
  }

  function times(iterator, context) {
    $R(0, this, true).each(iterator, context);
    return this;
  }

  function toPaddedString(length, radix) {
    var string = this.toString(radix || 10);
    return '0'.times(length - string.length) + string;
  }

  function abs() {
    return Math.abs(this);
  }

  function round() {
    return Math.round(this);
  }

  function ceil() {
    return Math.ceil(this);
  }

  function floor() {
    return Math.floor(this);
  }

  return {
    toColorPart:    toColorPart,
    succ:           succ,
    times:          times,
    toPaddedString: toPaddedString,
    abs:            abs,
    round:          round,
    ceil:           ceil,
    floor:          floor
  };
})());

function $R(start, end, exclusive) {
  return new ObjectRange(start, end, exclusive);
}

var ObjectRange = Class.create(Enumerable, (function() {
  function initialize(start, end, exclusive) {
    this.start = start;
    this.end = end;
    this.exclusive = exclusive;
  }

  function _each(iterator) {
    var value = this.start;
    while (this.include(value)) {
      iterator(value);
      value = value.succ();
    }
  }

  function include(value) {
    if (value < this.start)
      return false;
    if (this.exclusive)
      return value < this.end;
    return value <= this.end;
  }

  return {
    initialize: initialize,
    _each:      _each,
    include:    include
  };
})());



var Abstract = { };


var Try = {
  these: function() {
    var returnValue;

    for (var i = 0, length = arguments.length; i < length; i++) {
      var lambda = arguments[i];
      try {
        returnValue = lambda();
        break;
      } catch (e) { }
    }

    return returnValue;
  }
};

var Ajax = {
  getTransport: function() {
    return Try.these(
      function() {return new XMLHttpRequest()},
      function() {return new ActiveXObject('Msxml2.XMLHTTP')},
      function() {return new ActiveXObject('Microsoft.XMLHTTP')}
    ) || false;
  },

  activeRequestCount: 0
};

Ajax.Responders = {
  responders: [],

  _each: function(iterator) {
    this.responders._each(iterator);
  },

  register: function(responder) {
    if (!this.include(responder))
      this.responders.push(responder);
  },

  unregister: function(responder) {
    this.responders = this.responders.without(responder);
  },

  dispatch: function(callback, request, transport, json) {
    this.each(function(responder) {
      if (Object.isFunction(responder[callback])) {
        try {
          responder[callback].apply(responder, [request, transport, json]);
        } catch (e) { }
      }
    });
  }
};

Object.extend(Ajax.Responders, Enumerable);

Ajax.Responders.register({
  onCreate:   function() { Ajax.activeRequestCount++ },
  onComplete: function() { Ajax.activeRequestCount-- }
});
Ajax.Base = Class.create({
  initialize: function(options) {
    this.options = {
      method:       'post',
      asynchronous: true,
      contentType:  'application/x-www-form-urlencoded',
      encoding:     'UTF-8',
      parameters:   '',
      evalJSON:     true,
      evalJS:       true
    };
    Object.extend(this.options, options || { });

    this.options.method = this.options.method.toLowerCase();

    if (Object.isHash(this.options.parameters))
      this.options.parameters = this.options.parameters.toObject();
  }
});
Ajax.Request = Class.create(Ajax.Base, {
  _complete: false,

  initialize: function($super, url, options) {
    $super(options);
    this.transport = Ajax.getTransport();
    this.request(url);
  },

  request: function(url) {
    this.url = url;
    this.method = this.options.method;
    var params = Object.isString(this.options.parameters) ?
          this.options.parameters :
          Object.toQueryString(this.options.parameters);

    if (!['get', 'post'].include(this.method)) {
      params += (params ? '&' : '') + "_method=" + this.method;
      this.method = 'post';
    }

    if (params && this.method === 'get') {
      this.url += (this.url.include('?') ? '&' : '?') + params;
    }

    this.parameters = params.toQueryParams();

    try {
      var response = new Ajax.Response(this);
      if (this.options.onCreate) this.options.onCreate(response);
      Ajax.Responders.dispatch('onCreate', this, response);

      this.transport.open(this.method.toUpperCase(), this.url,
        this.options.asynchronous);

      if (this.options.asynchronous) this.respondToReadyState.bind(this).defer(1);

      this.transport.onreadystatechange = this.onStateChange.bind(this);
      this.setRequestHeaders();

      this.body = this.method == 'post' ? (this.options.postBody || params) : null;
      this.transport.send(this.body);

      /* Force Firefox to handle ready state 4 for synchronous requests */
      if (!this.options.asynchronous && this.transport.overrideMimeType)
        this.onStateChange();

    }
    catch (e) {
      this.dispatchException(e);
    }
  },

  onStateChange: function() {
    var readyState = this.transport.readyState;
    if (readyState > 1 && !((readyState == 4) && this._complete))
      this.respondToReadyState(this.transport.readyState);
  },

  setRequestHeaders: function() {
    var headers = {
      'X-Requested-With': 'XMLHttpRequest',
      'X-Prototype-Version': Prototype.Version,
      'Accept': 'text/javascript, text/html, application/xml, text/xml, */*'
    };

    if (this.method == 'post') {
      headers['Content-type'] = this.options.contentType +
        (this.options.encoding ? '; charset=' + this.options.encoding : '');

      /* Force "Connection: close" for older Mozilla browsers to work
       * around a bug where XMLHttpRequest sends an incorrect
       * Content-length header. See Mozilla Bugzilla #246651.
       */
      if (this.transport.overrideMimeType &&
          (navigator.userAgent.match(/Gecko\/(\d{4})/) || [0,2005])[1] < 2005)
            headers['Connection'] = 'close';
    }

    if (typeof this.options.requestHeaders == 'object') {
      var extras = this.options.requestHeaders;

      if (Object.isFunction(extras.push))
        for (var i = 0, length = extras.length; i < length; i += 2)
          headers[extras[i]] = extras[i+1];
      else
        $H(extras).each(function(pair) { headers[pair.key] = pair.value });
    }

    for (var name in headers)
      this.transport.setRequestHeader(name, headers[name]);
  },

  success: function() {
    var status = this.getStatus();
    return !status || (status >= 200 && status < 300) || status == 304;
  },

  getStatus: function() {
    try {
      if (this.transport.status === 1223) return 204;
      return this.transport.status || 0;
    } catch (e) { return 0 }
  },

  respondToReadyState: function(readyState) {
    var state = Ajax.Request.Events[readyState], response = new Ajax.Response(this);

    if (state == 'Complete') {
      try {
        this._complete = true;
        (this.options['on' + response.status]
         || this.options['on' + (this.success() ? 'Success' : 'Failure')]
         || Prototype.emptyFunction)(response, response.headerJSON);
      } catch (e) {
        this.dispatchException(e);
      }

      var contentType = response.getHeader('Content-type');
      if (this.options.evalJS == 'force'
          || (this.options.evalJS && this.isSameOrigin() && contentType
          && contentType.match(/^\s*(text|application)\/(x-)?(java|ecma)script(;.*)?\s*$/i)))
        this.evalResponse();
    }

    try {
      (this.options['on' + state] || Prototype.emptyFunction)(response, response.headerJSON);
      Ajax.Responders.dispatch('on' + state, this, response, response.headerJSON);
    } catch (e) {
      this.dispatchException(e);
    }

    if (state == 'Complete') {
      this.transport.onreadystatechange = Prototype.emptyFunction;
    }
  },

  isSameOrigin: function() {
    var m = this.url.match(/^\s*https?:\/\/[^\/]*/);
    return !m || (m[0] == '#{protocol}//#{domain}#{port}'.interpolate({
      protocol: location.protocol,
      domain: document.domain,
      port: location.port ? ':' + location.port : ''
    }));
  },

  getHeader: function(name) {
    try {
      return this.transport.getResponseHeader(name) || null;
    } catch (e) { return null; }
  },

  evalResponse: function() {
    try {
      return eval((this.transport.responseText || '').unfilterJSON());
    } catch (e) {
      this.dispatchException(e);
    }
  },

  dispatchException: function(exception) {
    (this.options.onException || Prototype.emptyFunction)(this, exception);
    Ajax.Responders.dispatch('onException', this, exception);
  }
});

Ajax.Request.Events =
  ['Uninitialized', 'Loading', 'Loaded', 'Interactive', 'Complete'];








Ajax.Response = Class.create({
  initialize: function(request){
    this.request = request;
    var transport  = this.transport  = request.transport,
        readyState = this.readyState = transport.readyState;

    if ((readyState > 2 && !Prototype.Browser.IE) || readyState == 4) {
      this.status       = this.getStatus();
      this.statusText   = this.getStatusText();
      this.responseText = String.interpret(transport.responseText);
      this.headerJSON   = this._getHeaderJSON();
    }

    if (readyState == 4) {
      var xml = transport.responseXML;
      this.responseXML  = Object.isUndefined(xml) ? null : xml;
      this.responseJSON = this._getResponseJSON();
    }
  },

  status:      0,

  statusText: '',

  getStatus: Ajax.Request.prototype.getStatus,

  getStatusText: function() {
    try {
      return this.transport.statusText || '';
    } catch (e) { return '' }
  },

  getHeader: Ajax.Request.prototype.getHeader,

  getAllHeaders: function() {
    try {
      return this.getAllResponseHeaders();
    } catch (e) { return null }
  },

  getResponseHeader: function(name) {
    return this.transport.getResponseHeader(name);
  },

  getAllResponseHeaders: function() {
    return this.transport.getAllResponseHeaders();
  },

  _getHeaderJSON: function() {
    var json = this.getHeader('X-JSON');
    if (!json) return null;
    json = decodeURIComponent(escape(json));
    try {
      return json.evalJSON(this.request.options.sanitizeJSON ||
        !this.request.isSameOrigin());
    } catch (e) {
      this.request.dispatchException(e);
    }
  },

  _getResponseJSON: function() {
    var options = this.request.options;
    if (!options.evalJSON || (options.evalJSON != 'force' &&
      !(this.getHeader('Content-type') || '').include('application/json')) ||
        this.responseText.blank())
          return null;
    try {
      return this.responseText.evalJSON(options.sanitizeJSON ||
        !this.request.isSameOrigin());
    } catch (e) {
      this.request.dispatchException(e);
    }
  }
});

Ajax.Updater = Class.create(Ajax.Request, {
  initialize: function($super, container, url, options) {
    this.container = {
      success: (container.success || container),
      failure: (container.failure || (container.success ? null : container))
    };

    options = Object.clone(options);
    var onComplete = options.onComplete;
    options.onComplete = (function(response, json) {
      this.updateContent(response.responseText);
      if (Object.isFunction(onComplete)) onComplete(response, json);
    }).bind(this);

    $super(url, options);
  },

  updateContent: function(responseText) {
    var receiver = this.container[this.success() ? 'success' : 'failure'],
        options = this.options;

    if (!options.evalScripts) responseText = responseText.stripScripts();

    if (receiver = $(receiver)) {
      if (options.insertion) {
        if (Object.isString(options.insertion)) {
          var insertion = { }; insertion[options.insertion] = responseText;
          receiver.insert(insertion);
        }
        else options.insertion(receiver, responseText);
      }
      else receiver.update(responseText);
    }
  }
});

Ajax.PeriodicalUpdater = Class.create(Ajax.Base, {
  initialize: function($super, container, url, options) {
    $super(options);
    this.onComplete = this.options.onComplete;

    this.frequency = (this.options.frequency || 2);
    this.decay = (this.options.decay || 1);

    this.updater = { };
    this.container = container;
    this.url = url;

    this.start();
  },

  start: function() {
    this.options.onComplete = this.updateComplete.bind(this);
    this.onTimerEvent();
  },

  stop: function() {
    this.updater.options.onComplete = undefined;
    clearTimeout(this.timer);
    (this.onComplete || Prototype.emptyFunction).apply(this, arguments);
  },

  updateComplete: function(response) {
    if (this.options.decay) {
      this.decay = (response.responseText == this.lastText ?
        this.decay * this.options.decay : 1);

      this.lastText = response.responseText;
    }
    this.timer = this.onTimerEvent.bind(this).delay(this.decay * this.frequency);
  },

  onTimerEvent: function() {
    this.updater = new Ajax.Updater(this.container, this.url, this.options);
  }
});


function $(element) {
  if (arguments.length > 1) {
    for (var i = 0, elements = [], length = arguments.length; i < length; i++)
      elements.push($(arguments[i]));
    return elements;
  }
  if (Object.isString(element))
    element = document.getElementById(element);
  return Element.extend(element);
}

if (Prototype.BrowserFeatures.XPath) {
  document._getElementsByXPath = function(expression, parentElement) {
    var results = [];
    var query = document.evaluate(expression, $(parentElement) || document,
      null, XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);
    for (var i = 0, length = query.snapshotLength; i < length; i++)
      results.push(Element.extend(query.snapshotItem(i)));
    return results;
  };
}

/*--------------------------------------------------------------------------*/

if (!Node) var Node = { };

if (!Node.ELEMENT_NODE) {
  Object.extend(Node, {
    ELEMENT_NODE: 1,
    ATTRIBUTE_NODE: 2,
    TEXT_NODE: 3,
    CDATA_SECTION_NODE: 4,
    ENTITY_REFERENCE_NODE: 5,
    ENTITY_NODE: 6,
    PROCESSING_INSTRUCTION_NODE: 7,
    COMMENT_NODE: 8,
    DOCUMENT_NODE: 9,
    DOCUMENT_TYPE_NODE: 10,
    DOCUMENT_FRAGMENT_NODE: 11,
    NOTATION_NODE: 12
  });
}



(function(global) {
  function shouldUseCache(tagName, attributes) {
    if (tagName === 'select') return false;
    if ('type' in attributes) return false;
    return true;
  }

  var HAS_EXTENDED_CREATE_ELEMENT_SYNTAX = (function(){
    try {
      var el = document.createElement('<input name="x">');
      return el.tagName.toLowerCase() === 'input' && el.name === 'x';
    }
    catch(err) {
      return false;
    }
  })();

  var element = global.Element;

  global.Element = function(tagName, attributes) {
    attributes = attributes || { };
    tagName = tagName.toLowerCase();
    var cache = Element.cache;

    if (HAS_EXTENDED_CREATE_ELEMENT_SYNTAX && attributes.name) {
      tagName = '<' + tagName + ' name="' + attributes.name + '">';
      delete attributes.name;
      return Element.writeAttribute(document.createElement(tagName), attributes);
    }

    if (!cache[tagName]) cache[tagName] = Element.extend(document.createElement(tagName));

    var node = shouldUseCache(tagName, attributes) ?
     cache[tagName].cloneNode(false) : document.createElement(tagName);

    return Element.writeAttribute(node, attributes);
  };

  Object.extend(global.Element, element || { });
  if (element) global.Element.prototype = element.prototype;

})(this);

Element.idCounter = 1;
Element.cache = { };

Element._purgeElement = function(element) {
  var uid = element._prototypeUID;
  if (uid) {
    Element.stopObserving(element);
    element._prototypeUID = void 0;
    delete Element.Storage[uid];
  }
}

Element.Methods = {
  visible: function(element) {
    return $(element).style.display != 'none';
  },

  toggle: function(element) {
    element = $(element);
    Element[Element.visible(element) ? 'hide' : 'show'](element);
    return element;
  },

  hide: function(element) {
    element = $(element);
    element.style.display = 'none';
    return element;
  },

  show: function(element) {
    element = $(element);
    element.style.display = '';
    return element;
  },

  remove: function(element) {
    element = $(element);
    element.parentNode.removeChild(element);
    return element;
  },

  update: (function(){

    var SELECT_ELEMENT_INNERHTML_BUGGY = (function(){
      var el = document.createElement("select"),
          isBuggy = true;
      el.innerHTML = "<option value=\"test\">test</option>";
      if (el.options && el.options[0]) {
        isBuggy = el.options[0].nodeName.toUpperCase() !== "OPTION";
      }
      el = null;
      return isBuggy;
    })();

    var TABLE_ELEMENT_INNERHTML_BUGGY = (function(){
      try {
        var el = document.createElement("table");
        if (el && el.tBodies) {
          el.innerHTML = "<tbody><tr><td>test</td></tr></tbody>";
          var isBuggy = typeof el.tBodies[0] == "undefined";
          el = null;
          return isBuggy;
        }
      } catch (e) {
        return true;
      }
    })();

    var LINK_ELEMENT_INNERHTML_BUGGY = (function() {
      try {
        var el = document.createElement('div');
        el.innerHTML = "<link>";
        var isBuggy = (el.childNodes.length === 0);
        el = null;
        return isBuggy;
      } catch(e) {
        return true;
      }
    })();

    var ANY_INNERHTML_BUGGY = SELECT_ELEMENT_INNERHTML_BUGGY ||
     TABLE_ELEMENT_INNERHTML_BUGGY || LINK_ELEMENT_INNERHTML_BUGGY;

    var SCRIPT_ELEMENT_REJECTS_TEXTNODE_APPENDING = (function () {
      var s = document.createElement("script"),
          isBuggy = false;
      try {
        s.appendChild(document.createTextNode(""));
        isBuggy = !s.firstChild ||
          s.firstChild && s.firstChild.nodeType !== 3;
      } catch (e) {
        isBuggy = true;
      }
      s = null;
      return isBuggy;
    })();


    function update(element, content) {
      element = $(element);
      var purgeElement = Element._purgeElement;

      var descendants = element.getElementsByTagName('*'),
       i = descendants.length;
      while (i--) purgeElement(descendants[i]);

      if (content && content.toElement)
        content = content.toElement();

      if (Object.isElement(content))
        return element.update().insert(content);

      content = Object.toHTML(content);

      var tagName = element.tagName.toUpperCase();

      if (tagName === 'SCRIPT' && SCRIPT_ELEMENT_REJECTS_TEXTNODE_APPENDING) {
        element.text = content;
        return element;
      }

      if (ANY_INNERHTML_BUGGY) {
        if (tagName in Element._insertionTranslations.tags) {
          while (element.firstChild) {
            element.removeChild(element.firstChild);
          }
          Element._getContentFromAnonymousElement(tagName, content.stripScripts())
            .each(function(node) {
              element.appendChild(node)
            });
        } else if (LINK_ELEMENT_INNERHTML_BUGGY && Object.isString(content) && content.indexOf('<link') > -1) {
          while (element.firstChild) {
            element.removeChild(element.firstChild);
          }
          var nodes = Element._getContentFromAnonymousElement(tagName, content.stripScripts(), true);
          nodes.each(function(node) { element.appendChild(node) });
        }
        else {
          element.innerHTML = content.stripScripts();
        }
      }
      else {
        element.innerHTML = content.stripScripts();
      }

      content.evalScripts.bind(content).defer();
      return element;
    }

    return update;
  })(),

  replace: function(element, content) {
    element = $(element);
    if (content && content.toElement) content = content.toElement();
    else if (!Object.isElement(content)) {
      content = Object.toHTML(content);
      var range = element.ownerDocument.createRange();
      range.selectNode(element);
      content.evalScripts.bind(content).defer();
      content = range.createContextualFragment(content.stripScripts());
    }
    element.parentNode.replaceChild(content, element);
    return element;
  },

  insert: function(element, insertions) {
    element = $(element);

    if (Object.isString(insertions) || Object.isNumber(insertions) ||
        Object.isElement(insertions) || (insertions && (insertions.toElement || insertions.toHTML)))
          insertions = {bottom:insertions};

    var content, insert, tagName, childNodes;

    for (var position in insertions) {
      content  = insertions[position];
      position = position.toLowerCase();
      insert = Element._insertionTranslations[position];

      if (content && content.toElement) content = content.toElement();
      if (Object.isElement(content)) {
        insert(element, content);
        continue;
      }

      content = Object.toHTML(content);

      tagName = ((position == 'before' || position == 'after')
        ? element.parentNode : element).tagName.toUpperCase();

      childNodes = Element._getContentFromAnonymousElement(tagName, content.stripScripts());

      if (position == 'top' || position == 'after') childNodes.reverse();
      childNodes.each(insert.curry(element));

      content.evalScripts.bind(content).defer();
    }

    return element;
  },

  wrap: function(element, wrapper, attributes) {
    element = $(element);
    if (Object.isElement(wrapper))
      $(wrapper).writeAttribute(attributes || { });
    else if (Object.isString(wrapper)) wrapper = new Element(wrapper, attributes);
    else wrapper = new Element('div', wrapper);
    if (element.parentNode)
      element.parentNode.replaceChild(wrapper, element);
    wrapper.appendChild(element);
    return wrapper;
  },

  inspect: function(element) {
    element = $(element);
    var result = '<' + element.tagName.toLowerCase();
    $H({'id': 'id', 'className': 'class'}).each(function(pair) {
      var property = pair.first(),
          attribute = pair.last(),
          value = (element[property] || '').toString();
      if (value) result += ' ' + attribute + '=' + value.inspect(true);
    });
    return result + '>';
  },

  recursivelyCollect: function(element, property, maximumLength) {
    element = $(element);
    maximumLength = maximumLength || -1;
    var elements = [];

    while (element = element[property]) {
      if (element.nodeType == 1)
        elements.push(Element.extend(element));
      if (elements.length == maximumLength)
        break;
    }

    return elements;
  },

  ancestors: function(element) {
    return Element.recursivelyCollect(element, 'parentNode');
  },

  descendants: function(element) {
    return Element.select(element, "*");
  },

  firstDescendant: function(element) {
    element = $(element).firstChild;
    while (element && element.nodeType != 1) element = element.nextSibling;
    return $(element);
  },

  immediateDescendants: function(element) {
    var results = [], child = $(element).firstChild;
    while (child) {
      if (child.nodeType === 1) {
        results.push(Element.extend(child));
      }
      child = child.nextSibling;
    }
    return results;
  },

  previousSiblings: function(element, maximumLength) {
    return Element.recursivelyCollect(element, 'previousSibling');
  },

  nextSiblings: function(element) {
    return Element.recursivelyCollect(element, 'nextSibling');
  },

  siblings: function(element) {
    element = $(element);
    return Element.previousSiblings(element).reverse()
      .concat(Element.nextSiblings(element));
  },

  match: function(element, selector) {
    element = $(element);
    if (Object.isString(selector))
      return Prototype.Selector.match(element, selector);
    return selector.match(element);
  },

  up: function(element, expression, index) {
    element = $(element);
    if (arguments.length == 1) return $(element.parentNode);
    var ancestors = Element.ancestors(element);
    return Object.isNumber(expression) ? ancestors[expression] :
      Prototype.Selector.find(ancestors, expression, index);
  },

  down: function(element, expression, index) {
    element = $(element);
    if (arguments.length == 1) return Element.firstDescendant(element);
    return Object.isNumber(expression) ? Element.descendants(element)[expression] :
      Element.select(element, expression)[index || 0];
  },

  previous: function(element, expression, index) {
    element = $(element);
    if (Object.isNumber(expression)) index = expression, expression = false;
    if (!Object.isNumber(index)) index = 0;

    if (expression) {
      return Prototype.Selector.find(element.previousSiblings(), expression, index);
    } else {
      return element.recursivelyCollect("previousSibling", index + 1)[index];
    }
  },

  next: function(element, expression, index) {
    element = $(element);
    if (Object.isNumber(expression)) index = expression, expression = false;
    if (!Object.isNumber(index)) index = 0;

    if (expression) {
      return Prototype.Selector.find(element.nextSiblings(), expression, index);
    } else {
      var maximumLength = Object.isNumber(index) ? index + 1 : 1;
      return element.recursivelyCollect("nextSibling", index + 1)[index];
    }
  },


  select: function(element) {
    element = $(element);
    var expressions = Array.prototype.slice.call(arguments, 1).join(', ');
    return Prototype.Selector.select(expressions, element);
  },

  adjacent: function(element) {
    element = $(element);
    var expressions = Array.prototype.slice.call(arguments, 1).join(', ');
    return Prototype.Selector.select(expressions, element.parentNode).without(element);
  },

  identify: function(element) {
    element = $(element);
    var id = Element.readAttribute(element, 'id');
    if (id) return id;
    do { id = 'anonymous_element_' + Element.idCounter++ } while ($(id));
    Element.writeAttribute(element, 'id', id);
    return id;
  },

  readAttribute: function(element, name) {
    element = $(element);
    if (Prototype.Browser.IE) {
      var t = Element._attributeTranslations.read;
      if (t.values[name]) return t.values[name](element, name);
      if (t.names[name]) name = t.names[name];
      if (name.include(':')) {
        return (!element.attributes || !element.attributes[name]) ? null :
         element.attributes[name].value;
      }
    }
    return element.getAttribute(name);
  },

  writeAttribute: function(element, name, value) {
    element = $(element);
    var attributes = { }, t = Element._attributeTranslations.write;

    if (typeof name == 'object') attributes = name;
    else attributes[name] = Object.isUndefined(value) ? true : value;

    for (var attr in attributes) {
      name = t.names[attr] || attr;
      value = attributes[attr];
      if (t.values[attr]) name = t.values[attr](element, value);
      if (value === false || value === null)
        element.removeAttribute(name);
      else if (value === true)
        element.setAttribute(name, name);
      else element.setAttribute(name, value);
    }
    return element;
  },

  getHeight: function(element) {
    return Element.getDimensions(element).height;
  },

  getWidth: function(element) {
    return Element.getDimensions(element).width;
  },

  classNames: function(element) {
    return new Element.ClassNames(element);
  },

  hasClassName: function(element, className) {
    if (!(element = $(element))) return;
    var elementClassName = element.className;
    return (elementClassName.length > 0 && (elementClassName == className ||
      new RegExp("(^|\\s)" + className + "(\\s|$)").test(elementClassName)));
  },

  addClassName: function(element, className) {
    if (!(element = $(element))) return;
    if (!Element.hasClassName(element, className))
      element.className += (element.className ? ' ' : '') + className;
    return element;
  },

  removeClassName: function(element, className) {
    if (!(element = $(element))) return;
    element.className = element.className.replace(
      new RegExp("(^|\\s+)" + className + "(\\s+|$)"), ' ').strip();
    return element;
  },

  toggleClassName: function(element, className) {
    if (!(element = $(element))) return;
    return Element[Element.hasClassName(element, className) ?
      'removeClassName' : 'addClassName'](element, className);
  },

  cleanWhitespace: function(element) {
    element = $(element);
    var node = element.firstChild;
    while (node) {
      var nextNode = node.nextSibling;
      if (node.nodeType == 3 && !/\S/.test(node.nodeValue))
        element.removeChild(node);
      node = nextNode;
    }
    return element;
  },

  empty: function(element) {
    return $(element).innerHTML.blank();
  },

  descendantOf: function(element, ancestor) {
    element = $(element), ancestor = $(ancestor);

    if (element.compareDocumentPosition)
      return (element.compareDocumentPosition(ancestor) & 8) === 8;

    if (ancestor.contains)
      return ancestor.contains(element) && ancestor !== element;

    while (element = element.parentNode)
      if (element == ancestor) return true;

    return false;
  },

  scrollTo: function(element) {
    element = $(element);
    var pos = Element.cumulativeOffset(element);
    window.scrollTo(pos[0], pos[1]);
    return element;
  },

  getStyle: function(element, style) {
    element = $(element);
    style = style == 'float' ? 'cssFloat' : style.camelize();
    var value = element.style[style];
    if (!value || value == 'auto') {
      var css = document.defaultView.getComputedStyle(element, null);
      value = css ? css[style] : null;
    }
    if (style == 'opacity') return value ? parseFloat(value) : 1.0;
    return value == 'auto' ? null : value;
  },

  getOpacity: function(element) {
    return $(element).getStyle('opacity');
  },

  setStyle: function(element, styles) {
    element = $(element);
    var elementStyle = element.style, match;
    if (Object.isString(styles)) {
      element.style.cssText += ';' + styles;
      return styles.include('opacity') ?
        element.setOpacity(styles.match(/opacity:\s*(\d?\.?\d*)/)[1]) : element;
    }
    for (var property in styles)
      if (property == 'opacity') element.setOpacity(styles[property]);
      else
        elementStyle[(property == 'float' || property == 'cssFloat') ?
          (Object.isUndefined(elementStyle.styleFloat) ? 'cssFloat' : 'styleFloat') :
            property] = styles[property];

    return element;
  },

  setOpacity: function(element, value) {
    element = $(element);
    element.style.opacity = (value == 1 || value === '') ? '' :
      (value < 0.00001) ? 0 : value;
    return element;
  },

  makePositioned: function(element) {
    element = $(element);
    var pos = Element.getStyle(element, 'position');
    if (pos == 'static' || !pos) {
      element._madePositioned = true;
      element.style.position = 'relative';
      if (Prototype.Browser.Opera) {
        element.style.top = 0;
        element.style.left = 0;
      }
    }
    return element;
  },

  undoPositioned: function(element) {
    element = $(element);
    if (element._madePositioned) {
      element._madePositioned = undefined;
      element.style.position =
        element.style.top =
        element.style.left =
        element.style.bottom =
        element.style.right = '';
    }
    return element;
  },

  makeClipping: function(element) {
    element = $(element);
    if (element._overflow) return element;
    element._overflow = Element.getStyle(element, 'overflow') || 'auto';
    if (element._overflow !== 'hidden')
      element.style.overflow = 'hidden';
    return element;
  },

  undoClipping: function(element) {
    element = $(element);
    if (!element._overflow) return element;
    element.style.overflow = element._overflow == 'auto' ? '' : element._overflow;
    element._overflow = null;
    return element;
  },

  clonePosition: function(element, source) {
    var options = Object.extend({
      setLeft:    true,
      setTop:     true,
      setWidth:   true,
      setHeight:  true,
      offsetTop:  0,
      offsetLeft: 0
    }, arguments[2] || { });

    source = $(source);
    var p = Element.viewportOffset(source), delta = [0, 0], parent = null;

    element = $(element);

    if (Element.getStyle(element, 'position') == 'absolute') {
      parent = Element.getOffsetParent(element);
      delta = Element.viewportOffset(parent);
    }

    if (parent == document.body) {
      delta[0] -= document.body.offsetLeft;
      delta[1] -= document.body.offsetTop;
    }

    if (options.setLeft)   element.style.left  = (p[0] - delta[0] + options.offsetLeft) + 'px';
    if (options.setTop)    element.style.top   = (p[1] - delta[1] + options.offsetTop) + 'px';
    if (options.setWidth)  element.style.width = source.offsetWidth + 'px';
    if (options.setHeight) element.style.height = source.offsetHeight + 'px';
    return element;
  }
};

Object.extend(Element.Methods, {
  getElementsBySelector: Element.Methods.select,

  childElements: Element.Methods.immediateDescendants
});

Element._attributeTranslations = {
  write: {
    names: {
      className: 'class',
      htmlFor:   'for'
    },
    values: { }
  }
};

if (Prototype.Browser.Opera) {
  Element.Methods.getStyle = Element.Methods.getStyle.wrap(
    function(proceed, element, style) {
      switch (style) {
        case 'height': case 'width':
          if (!Element.visible(element)) return null;

          var dim = parseInt(proceed(element, style), 10);

          if (dim !== element['offset' + style.capitalize()])
            return dim + 'px';

          var properties;
          if (style === 'height') {
            properties = ['border-top-width', 'padding-top',
             'padding-bottom', 'border-bottom-width'];
          }
          else {
            properties = ['border-left-width', 'padding-left',
             'padding-right', 'border-right-width'];
          }
          return properties.inject(dim, function(memo, property) {
            var val = proceed(element, property);
            return val === null ? memo : memo - parseInt(val, 10);
          }) + 'px';
        default: return proceed(element, style);
      }
    }
  );

  Element.Methods.readAttribute = Element.Methods.readAttribute.wrap(
    function(proceed, element, attribute) {
      if (attribute === 'title') return element.title;
      return proceed(element, attribute);
    }
  );
}

else if (Prototype.Browser.IE) {
  Element.Methods.getStyle = function(element, style) {
    element = $(element);
    style = (style == 'float' || style == 'cssFloat') ? 'styleFloat' : style.camelize();
    var value = element.style[style];
    if (!value && element.currentStyle) value = element.currentStyle[style];

    if (style == 'opacity') {
      if (value = (element.getStyle('filter') || '').match(/alpha\(opacity=(.*)\)/))
        if (value[1]) return parseFloat(value[1]) / 100;
      return 1.0;
    }

    if (value == 'auto') {
      if ((style == 'width' || style == 'height') && (element.getStyle('display') != 'none'))
        return element['offset' + style.capitalize()] + 'px';
      return null;
    }
    return value;
  };

  Element.Methods.setOpacity = function(element, value) {
    function stripAlpha(filter){
      return filter.replace(/alpha\([^\)]*\)/gi,'');
    }
    element = $(element);
    var currentStyle = element.currentStyle;
    if ((currentStyle && !currentStyle.hasLayout) ||
      (!currentStyle && element.style.zoom == 'normal'))
        element.style.zoom = 1;

    var filter = element.getStyle('filter'), style = element.style;
    if (value == 1 || value === '') {
      (filter = stripAlpha(filter)) ?
        style.filter = filter : style.removeAttribute('filter');
      return element;
    } else if (value < 0.00001) value = 0;
    style.filter = stripAlpha(filter) +
      'alpha(opacity=' + (value * 100) + ')';
    return element;
  };

  Element._attributeTranslations = (function(){

    var classProp = 'className',
        forProp = 'for',
        el = document.createElement('div');

    el.setAttribute(classProp, 'x');

    if (el.className !== 'x') {
      el.setAttribute('class', 'x');
      if (el.className === 'x') {
        classProp = 'class';
      }
    }
    el = null;

    el = document.createElement('label');
    el.setAttribute(forProp, 'x');
    if (el.htmlFor !== 'x') {
      el.setAttribute('htmlFor', 'x');
      if (el.htmlFor === 'x') {
        forProp = 'htmlFor';
      }
    }
    el = null;

    return {
      read: {
        names: {
          'class':      classProp,
          'className':  classProp,
          'for':        forProp,
          'htmlFor':    forProp
        },
        values: {
          _getAttr: function(element, attribute) {
            return element.getAttribute(attribute);
          },
          _getAttr2: function(element, attribute) {
            return element.getAttribute(attribute, 2);
          },
          _getAttrNode: function(element, attribute) {
            var node = element.getAttributeNode(attribute);
            return node ? node.value : "";
          },
          _getEv: (function(){

            var el = document.createElement('div'), f;
            el.onclick = Prototype.emptyFunction;
            var value = el.getAttribute('onclick');

            if (String(value).indexOf('{') > -1) {
              f = function(element, attribute) {
                attribute = element.getAttribute(attribute);
                if (!attribute) return null;
                attribute = attribute.toString();
                attribute = attribute.split('{')[1];
                attribute = attribute.split('}')[0];
                return attribute.strip();
              };
            }
            else if (value === '') {
              f = function(element, attribute) {
                attribute = element.getAttribute(attribute);
                if (!attribute) return null;
                return attribute.strip();
              };
            }
            el = null;
            return f;
          })(),
          _flag: function(element, attribute) {
            return $(element).hasAttribute(attribute) ? attribute : null;
          },
          style: function(element) {
            return element.style.cssText.toLowerCase();
          },
          title: function(element) {
            return element.title;
          }
        }
      }
    }
  })();

  Element._attributeTranslations.write = {
    names: Object.extend({
      cellpadding: 'cellPadding',
      cellspacing: 'cellSpacing'
    }, Element._attributeTranslations.read.names),
    values: {
      checked: function(element, value) {
        element.checked = !!value;
      },

      style: function(element, value) {
        element.style.cssText = value ? value : '';
      }
    }
  };

  Element._attributeTranslations.has = {};

  $w('colSpan rowSpan vAlign dateTime accessKey tabIndex ' +
      'encType maxLength readOnly longDesc frameBorder').each(function(attr) {
    Element._attributeTranslations.write.names[attr.toLowerCase()] = attr;
    Element._attributeTranslations.has[attr.toLowerCase()] = attr;
  });

  (function(v) {
    Object.extend(v, {
      href:        v._getAttr2,
      src:         v._getAttr2,
      type:        v._getAttr,
      action:      v._getAttrNode,
      disabled:    v._flag,
      checked:     v._flag,
      readonly:    v._flag,
      multiple:    v._flag,
      onload:      v._getEv,
      onunload:    v._getEv,
      onclick:     v._getEv,
      ondblclick:  v._getEv,
      onmousedown: v._getEv,
      onmouseup:   v._getEv,
      onmouseover: v._getEv,
      onmousemove: v._getEv,
      onmouseout:  v._getEv,
      onfocus:     v._getEv,
      onblur:      v._getEv,
      onkeypress:  v._getEv,
      onkeydown:   v._getEv,
      onkeyup:     v._getEv,
      onsubmit:    v._getEv,
      onreset:     v._getEv,
      onselect:    v._getEv,
      onchange:    v._getEv
    });
  })(Element._attributeTranslations.read.values);

  if (Prototype.BrowserFeatures.ElementExtensions) {
    (function() {
      function _descendants(element) {
        var nodes = element.getElementsByTagName('*'), results = [];
        for (var i = 0, node; node = nodes[i]; i++)
          if (node.tagName !== "!") // Filter out comment nodes.
            results.push(node);
        return results;
      }

      Element.Methods.down = function(element, expression, index) {
        element = $(element);
        if (arguments.length == 1) return element.firstDescendant();
        return Object.isNumber(expression) ? _descendants(element)[expression] :
          Element.select(element, expression)[index || 0];
      }
    })();
  }

}

else if (Prototype.Browser.Gecko && /rv:1\.8\.0/.test(navigator.userAgent)) {
  Element.Methods.setOpacity = function(element, value) {
    element = $(element);
    element.style.opacity = (value == 1) ? 0.999999 :
      (value === '') ? '' : (value < 0.00001) ? 0 : value;
    return element;
  };
}

else if (Prototype.Browser.WebKit) {
  Element.Methods.setOpacity = function(element, value) {
    element = $(element);
    element.style.opacity = (value == 1 || value === '') ? '' :
      (value < 0.00001) ? 0 : value;

    if (value == 1)
      if (element.tagName.toUpperCase() == 'IMG' && element.width) {
        element.width++; element.width--;
      } else try {
        var n = document.createTextNode(' ');
        element.appendChild(n);
        element.removeChild(n);
      } catch (e) { }

    return element;
  };
}

if ('outerHTML' in document.documentElement) {
  Element.Methods.replace = function(element, content) {
    element = $(element);

    if (content && content.toElement) content = content.toElement();
    if (Object.isElement(content)) {
      element.parentNode.replaceChild(content, element);
      return element;
    }

    content = Object.toHTML(content);
    var parent = element.parentNode, tagName = parent.tagName.toUpperCase();

    if (Element._insertionTranslations.tags[tagName]) {
      var nextSibling = element.next(),
          fragments = Element._getContentFromAnonymousElement(tagName, content.stripScripts());
      parent.removeChild(element);
      if (nextSibling)
        fragments.each(function(node) { parent.insertBefore(node, nextSibling) });
      else
        fragments.each(function(node) { parent.appendChild(node) });
    }
    else element.outerHTML = content.stripScripts();

    content.evalScripts.bind(content).defer();
    return element;
  };
}

Element._returnOffset = function(l, t) {
  var result = [l, t];
  result.left = l;
  result.top = t;
  return result;
};

Element._getContentFromAnonymousElement = function(tagName, html, force) {
  var div = new Element('div'),
      t = Element._insertionTranslations.tags[tagName];

  var workaround = false;
  if (t) workaround = true;
  else if (force) {
    workaround = true;
    t = ['', '', 0];
  }

  if (workaround) {
    div.innerHTML = '&nbsp;' + t[0] + html + t[1];
    div.removeChild(div.firstChild);
    for (var i = t[2]; i--; ) {
      div = div.firstChild;
    }
  }
  else {
    div.innerHTML = html;
  }
  return $A(div.childNodes);
};

Element._insertionTranslations = {
  before: function(element, node) {
    element.parentNode.insertBefore(node, element);
  },
  top: function(element, node) {
    element.insertBefore(node, element.firstChild);
  },
  bottom: function(element, node) {
    element.appendChild(node);
  },
  after: function(element, node) {
    element.parentNode.insertBefore(node, element.nextSibling);
  },
  tags: {
    TABLE:  ['<table>',                '</table>',                   1],
    TBODY:  ['<table><tbody>',         '</tbody></table>',           2],
    TR:     ['<table><tbody><tr>',     '</tr></tbody></table>',      3],
    TD:     ['<table><tbody><tr><td>', '</td></tr></tbody></table>', 4],
    SELECT: ['<select>',               '</select>',                  1]
  }
};

(function() {
  var tags = Element._insertionTranslations.tags;
  Object.extend(tags, {
    THEAD: tags.TBODY,
    TFOOT: tags.TBODY,
    TH:    tags.TD
  });
})();

Element.Methods.Simulated = {
  hasAttribute: function(element, attribute) {
    attribute = Element._attributeTranslations.has[attribute] || attribute;
    var node = $(element).getAttributeNode(attribute);
    return !!(node && node.specified);
  }
};

Element.Methods.ByTag = { };

Object.extend(Element, Element.Methods);

(function(div) {

  if (!Prototype.BrowserFeatures.ElementExtensions && div['__proto__']) {
    window.HTMLElement = { };
    window.HTMLElement.prototype = div['__proto__'];
    Prototype.BrowserFeatures.ElementExtensions = true;
  }

  div = null;

})(document.createElement('div'));

Element.extend = (function() {

  function checkDeficiency(tagName) {
    if (typeof window.Element != 'undefined') {
      var proto = window.Element.prototype;
      if (proto) {
        var id = '_' + (Math.random()+'').slice(2),
            el = document.createElement(tagName);
        proto[id] = 'x';
        var isBuggy = (el[id] !== 'x');
        delete proto[id];
        el = null;
        return isBuggy;
      }
    }
    return false;
  }

  function extendElementWith(element, methods) {
    for (var property in methods) {
      var value = methods[property];
      if (Object.isFunction(value) && !(property in element))
        element[property] = value.methodize();
    }
  }

  var HTMLOBJECTELEMENT_PROTOTYPE_BUGGY = checkDeficiency('object');

  if (Prototype.BrowserFeatures.SpecificElementExtensions) {
    if (HTMLOBJECTELEMENT_PROTOTYPE_BUGGY) {
      return function(element) {
        if (element && typeof element._extendedByPrototype == 'undefined') {
          var t = element.tagName;
          if (t && (/^(?:object|applet|embed)$/i.test(t))) {
            extendElementWith(element, Element.Methods);
            extendElementWith(element, Element.Methods.Simulated);
            extendElementWith(element, Element.Methods.ByTag[t.toUpperCase()]);
          }
        }
        return element;
      }
    }
    return Prototype.K;
  }

  var Methods = { }, ByTag = Element.Methods.ByTag;

  var extend = Object.extend(function(element) {
    if (!element || typeof element._extendedByPrototype != 'undefined' ||
        element.nodeType != 1 || element == window) return element;

    var methods = Object.clone(Methods),
        tagName = element.tagName.toUpperCase();

    if (ByTag[tagName]) Object.extend(methods, ByTag[tagName]);

    extendElementWith(element, methods);

    element._extendedByPrototype = Prototype.emptyFunction;
    return element;

  }, {
    refresh: function() {
      if (!Prototype.BrowserFeatures.ElementExtensions) {
        Object.extend(Methods, Element.Methods);
        Object.extend(Methods, Element.Methods.Simulated);
      }
    }
  });

  extend.refresh();
  return extend;
})();

if (document.documentElement.hasAttribute) {
  Element.hasAttribute = function(element, attribute) {
    return element.hasAttribute(attribute);
  };
}
else {
  Element.hasAttribute = Element.Methods.Simulated.hasAttribute;
}

Element.addMethods = function(methods) {
  var F = Prototype.BrowserFeatures, T = Element.Methods.ByTag;

  if (!methods) {
    Object.extend(Form, Form.Methods);
    Object.extend(Form.Element, Form.Element.Methods);
    Object.extend(Element.Methods.ByTag, {
      "FORM":     Object.clone(Form.Methods),
      "INPUT":    Object.clone(Form.Element.Methods),
      "SELECT":   Object.clone(Form.Element.Methods),
      "TEXTAREA": Object.clone(Form.Element.Methods),
      "BUTTON":   Object.clone(Form.Element.Methods)
    });
  }

  if (arguments.length == 2) {
    var tagName = methods;
    methods = arguments[1];
  }

  if (!tagName) Object.extend(Element.Methods, methods || { });
  else {
    if (Object.isArray(tagName)) tagName.each(extend);
    else extend(tagName);
  }

  function extend(tagName) {
    tagName = tagName.toUpperCase();
    if (!Element.Methods.ByTag[tagName])
      Element.Methods.ByTag[tagName] = { };
    Object.extend(Element.Methods.ByTag[tagName], methods);
  }

  function copy(methods, destination, onlyIfAbsent) {
    onlyIfAbsent = onlyIfAbsent || false;
    for (var property in methods) {
      var value = methods[property];
      if (!Object.isFunction(value)) continue;
      if (!onlyIfAbsent || !(property in destination))
        destination[property] = value.methodize();
    }
  }

  function findDOMClass(tagName) {
    var klass;
    var trans = {
      "OPTGROUP": "OptGroup", "TEXTAREA": "TextArea", "P": "Paragraph",
      "FIELDSET": "FieldSet", "UL": "UList", "OL": "OList", "DL": "DList",
      "DIR": "Directory", "H1": "Heading", "H2": "Heading", "H3": "Heading",
      "H4": "Heading", "H5": "Heading", "H6": "Heading", "Q": "Quote",
      "INS": "Mod", "DEL": "Mod", "A": "Anchor", "IMG": "Image", "CAPTION":
      "TableCaption", "COL": "TableCol", "COLGROUP": "TableCol", "THEAD":
      "TableSection", "TFOOT": "TableSection", "TBODY": "TableSection", "TR":
      "TableRow", "TH": "TableCell", "TD": "TableCell", "FRAMESET":
      "FrameSet", "IFRAME": "IFrame"
    };
    if (trans[tagName]) klass = 'HTML' + trans[tagName] + 'Element';
    if (window[klass]) return window[klass];
    klass = 'HTML' + tagName + 'Element';
    if (window[klass]) return window[klass];
    klass = 'HTML' + tagName.capitalize() + 'Element';
    if (window[klass]) return window[klass];

    var element = document.createElement(tagName),
        proto = element['__proto__'] || element.constructor.prototype;

    element = null;
    return proto;
  }

  var elementPrototype = window.HTMLElement ? HTMLElement.prototype :
   Element.prototype;

  if (F.ElementExtensions) {
    copy(Element.Methods, elementPrototype);
    copy(Element.Methods.Simulated, elementPrototype, true);
  }

  if (F.SpecificElementExtensions) {
    for (var tag in Element.Methods.ByTag) {
      var klass = findDOMClass(tag);
      if (Object.isUndefined(klass)) continue;
      copy(T[tag], klass.prototype);
    }
  }

  Object.extend(Element, Element.Methods);
  delete Element.ByTag;

  if (Element.extend.refresh) Element.extend.refresh();
  Element.cache = { };
};


document.viewport = {

  getDimensions: function() {
    return { width: this.getWidth(), height: this.getHeight() };
  },

  getScrollOffsets: function() {
    return Element._returnOffset(
      window.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft,
      window.pageYOffset || document.documentElement.scrollTop  || document.body.scrollTop);
  }
};

(function(viewport) {
  var B = Prototype.Browser, doc = document, element, property = {};

  function getRootElement() {
    if (B.WebKit && !doc.evaluate)
      return document;

    if (B.Opera && window.parseFloat(window.opera.version()) < 9.5)
      return document.body;

    return document.documentElement;
  }

  function define(D) {
    if (!element) element = getRootElement();

    property[D] = 'client' + D;

    viewport['get' + D] = function() { return element[property[D]] };
    return viewport['get' + D]();
  }

  viewport.getWidth  = define.curry('Width');

  viewport.getHeight = define.curry('Height');
})(document.viewport);


Element.Storage = {
  UID: 1
};

Element.addMethods({
  getStorage: function(element) {
    if (!(element = $(element))) return;

    var uid;
    if (element === window) {
      uid = 0;
    } else {
      if (typeof element._prototypeUID === "undefined")
        element._prototypeUID = Element.Storage.UID++;
      uid = element._prototypeUID;
    }

    if (!Element.Storage[uid])
      Element.Storage[uid] = $H();

    return Element.Storage[uid];
  },

  store: function(element, key, value) {
    if (!(element = $(element))) return;

    if (arguments.length === 2) {
      Element.getStorage(element).update(key);
    } else {
      Element.getStorage(element).set(key, value);
    }

    return element;
  },

  retrieve: function(element, key, defaultValue) {
    if (!(element = $(element))) return;
    var hash = Element.getStorage(element), value = hash.get(key);

    if (Object.isUndefined(value)) {
      hash.set(key, defaultValue);
      value = defaultValue;
    }

    return value;
  },

  clone: function(element, deep) {
    if (!(element = $(element))) return;
    var clone = element.cloneNode(deep);
    clone._prototypeUID = void 0;
    if (deep) {
      var descendants = Element.select(clone, '*'),
          i = descendants.length;
      while (i--) {
        descendants[i]._prototypeUID = void 0;
      }
    }
    return Element.extend(clone);
  },

  purge: function(element) {
    if (!(element = $(element))) return;
    var purgeElement = Element._purgeElement;

    purgeElement(element);

    var descendants = element.getElementsByTagName('*'),
     i = descendants.length;

    while (i--) purgeElement(descendants[i]);

    return null;
  }
});

(function() {

  function toDecimal(pctString) {
    var match = pctString.match(/^(\d+)%?$/i);
    if (!match) return null;
    return (Number(match[1]) / 100);
  }

  function getPixelValue(value, property, context) {
    var element = null;
    if (Object.isElement(value)) {
      element = value;
      value = element.getStyle(property);
    }

    if (value === null) {
      return null;
    }

    if ((/^(?:-)?\d+(\.\d+)?(px)?$/i).test(value)) {
      return window.parseFloat(value);
    }

    var isPercentage = value.include('%'), isViewport = (context === document.viewport);

    if (/\d/.test(value) && element && element.runtimeStyle && !(isPercentage && isViewport)) {
      var style = element.style.left, rStyle = element.runtimeStyle.left;
      element.runtimeStyle.left = element.currentStyle.left;
      element.style.left = value || 0;
      value = element.style.pixelLeft;
      element.style.left = style;
      element.runtimeStyle.left = rStyle;

      return value;
    }

    if (element && isPercentage) {
      context = context || element.parentNode;
      var decimal = toDecimal(value);
      var whole = null;
      var position = element.getStyle('position');

      var isHorizontal = property.include('left') || property.include('right') ||
       property.include('width');

      var isVertical =  property.include('top') || property.include('bottom') ||
        property.include('height');

      if (context === document.viewport) {
        if (isHorizontal) {
          whole = document.viewport.getWidth();
        } else if (isVertical) {
          whole = document.viewport.getHeight();
        }
      } else {
        if (isHorizontal) {
          whole = $(context).measure('width');
        } else if (isVertical) {
          whole = $(context).measure('height');
        }
      }

      return (whole === null) ? 0 : whole * decimal;
    }

    return 0;
  }

  function toCSSPixels(number) {
    if (Object.isString(number) && number.endsWith('px')) {
      return number;
    }
    return number + 'px';
  }

  function isDisplayed(element) {
    var originalElement = element;
    while (element && element.parentNode) {
      var display = element.getStyle('display');
      if (display === 'none') {
        return false;
      }
      element = $(element.parentNode);
    }
    return true;
  }

  var hasLayout = Prototype.K;
  if ('currentStyle' in document.documentElement) {
    hasLayout = function(element) {
      if (!element.currentStyle.hasLayout) {
        element.style.zoom = 1;
      }
      return element;
    };
  }

  function cssNameFor(key) {
    if (key.include('border')) key = key + '-width';
    return key.camelize();
  }

  Element.Layout = Class.create(Hash, {
    initialize: function($super, element, preCompute) {
      $super();
      this.element = $(element);

      Element.Layout.PROPERTIES.each( function(property) {
        this._set(property, null);
      }, this);

      if (preCompute) {
        this._preComputing = true;
        this._begin();
        Element.Layout.PROPERTIES.each( this._compute, this );
        this._end();
        this._preComputing = false;
      }
    },

    _set: function(property, value) {
      return Hash.prototype.set.call(this, property, value);
    },

    set: function(property, value) {
      throw "Properties of Element.Layout are read-only.";
    },

    get: function($super, property) {
      var value = $super(property);
      return value === null ? this._compute(property) : value;
    },

    _begin: function() {
      if (this._prepared) return;

      var element = this.element;
      if (isDisplayed(element)) {
        this._prepared = true;
        return;
      }

      var originalStyles = {
        position:   element.style.position   || '',
        width:      element.style.width      || '',
        visibility: element.style.visibility || '',
        display:    element.style.display    || ''
      };

      element.store('prototype_original_styles', originalStyles);

      var position = element.getStyle('position'),
       width = element.getStyle('width');

      if (width === "0px" || width === null) {
        element.style.display = 'block';
        width = element.getStyle('width');
      }

      var context = (position === 'fixed') ? document.viewport :
       element.parentNode;

      element.setStyle({
        position:   'absolute',
        visibility: 'hidden',
        display:    'block'
      });

      var positionedWidth = element.getStyle('width');

      var newWidth;
      if (width && (positionedWidth === width)) {
        newWidth = getPixelValue(element, 'width', context);
      } else if (position === 'absolute' || position === 'fixed') {
        newWidth = getPixelValue(element, 'width', context);
      } else {
        var parent = element.parentNode, pLayout = $(parent).getLayout();

        newWidth = pLayout.get('width') -
         this.get('margin-left') -
         this.get('border-left') -
         this.get('padding-left') -
         this.get('padding-right') -
         this.get('border-right') -
         this.get('margin-right');
      }

      element.setStyle({ width: newWidth + 'px' });

      this._prepared = true;
    },

    _end: function() {
      var element = this.element;
      var originalStyles = element.retrieve('prototype_original_styles');
      element.store('prototype_original_styles', null);
      element.setStyle(originalStyles);
      this._prepared = false;
    },

    _compute: function(property) {
      var COMPUTATIONS = Element.Layout.COMPUTATIONS;
      if (!(property in COMPUTATIONS)) {
        throw "Property not found.";
      }

      return this._set(property, COMPUTATIONS[property].call(this, this.element));
    },

    toObject: function() {
      var args = $A(arguments);
      var keys = (args.length === 0) ? Element.Layout.PROPERTIES :
       args.join(' ').split(' ');
      var obj = {};
      keys.each( function(key) {
        if (!Element.Layout.PROPERTIES.include(key)) return;
        var value = this.get(key);
        if (value != null) obj[key] = value;
      }, this);
      return obj;
    },

    toHash: function() {
      var obj = this.toObject.apply(this, arguments);
      return new Hash(obj);
    },

    toCSS: function() {
      var args = $A(arguments);
      var keys = (args.length === 0) ? Element.Layout.PROPERTIES :
       args.join(' ').split(' ');
      var css = {};

      keys.each( function(key) {
        if (!Element.Layout.PROPERTIES.include(key)) return;
        if (Element.Layout.COMPOSITE_PROPERTIES.include(key)) return;

        var value = this.get(key);
        if (value != null) css[cssNameFor(key)] = value + 'px';
      }, this);
      return css;
    },

    inspect: function() {
      return "#<Element.Layout>";
    }
  });

  Object.extend(Element.Layout, {
    PROPERTIES: $w('height width top left right bottom border-left border-right border-top border-bottom padding-left padding-right padding-top padding-bottom margin-top margin-bottom margin-left margin-right padding-box-width padding-box-height border-box-width border-box-height margin-box-width margin-box-height'),

    COMPOSITE_PROPERTIES: $w('padding-box-width padding-box-height margin-box-width margin-box-height border-box-width border-box-height'),

    COMPUTATIONS: {
      'height': function(element) {
        if (!this._preComputing) this._begin();

        var bHeight = this.get('border-box-height');
        if (bHeight <= 0) {
          if (!this._preComputing) this._end();
          return 0;
        }

        var bTop = this.get('border-top'),
         bBottom = this.get('border-bottom');

        var pTop = this.get('padding-top'),
         pBottom = this.get('padding-bottom');

        if (!this._preComputing) this._end();

        return bHeight - bTop - bBottom - pTop - pBottom;
      },

      'width': function(element) {
        if (!this._preComputing) this._begin();

        var bWidth = this.get('border-box-width');
        if (bWidth <= 0) {
          if (!this._preComputing) this._end();
          return 0;
        }

        var bLeft = this.get('border-left'),
         bRight = this.get('border-right');

        var pLeft = this.get('padding-left'),
         pRight = this.get('padding-right');

        if (!this._preComputing) this._end();

        return bWidth - bLeft - bRight - pLeft - pRight;
      },

      'padding-box-height': function(element) {
        var height = this.get('height'),
         pTop = this.get('padding-top'),
         pBottom = this.get('padding-bottom');

        return height + pTop + pBottom;
      },

      'padding-box-width': function(element) {
        var width = this.get('width'),
         pLeft = this.get('padding-left'),
         pRight = this.get('padding-right');

        return width + pLeft + pRight;
      },

      'border-box-height': function(element) {
        if (!this._preComputing) this._begin();
        var height = element.offsetHeight;
        if (!this._preComputing) this._end();
        return height;
      },

      'border-box-width': function(element) {
        if (!this._preComputing) this._begin();
        var width = element.offsetWidth;
        if (!this._preComputing) this._end();
        return width;
      },

      'margin-box-height': function(element) {
        var bHeight = this.get('border-box-height'),
         mTop = this.get('margin-top'),
         mBottom = this.get('margin-bottom');

        if (bHeight <= 0) return 0;

        return bHeight + mTop + mBottom;
      },

      'margin-box-width': function(element) {
        var bWidth = this.get('border-box-width'),
         mLeft = this.get('margin-left'),
         mRight = this.get('margin-right');

        if (bWidth <= 0) return 0;

        return bWidth + mLeft + mRight;
      },

      'top': function(element) {
        var offset = element.positionedOffset();
        return offset.top;
      },

      'bottom': function(element) {
        var offset = element.positionedOffset(),
         parent = element.getOffsetParent(),
         pHeight = parent.measure('height');

        var mHeight = this.get('border-box-height');

        return pHeight - mHeight - offset.top;
      },

      'left': function(element) {
        var offset = element.positionedOffset();
        return offset.left;
      },

      'right': function(element) {
        var offset = element.positionedOffset(),
         parent = element.getOffsetParent(),
         pWidth = parent.measure('width');

        var mWidth = this.get('border-box-width');

        return pWidth - mWidth - offset.left;
      },

      'padding-top': function(element) {
        return getPixelValue(element, 'paddingTop');
      },

      'padding-bottom': function(element) {
        return getPixelValue(element, 'paddingBottom');
      },

      'padding-left': function(element) {
        return getPixelValue(element, 'paddingLeft');
      },

      'padding-right': function(element) {
        return getPixelValue(element, 'paddingRight');
      },

      'border-top': function(element) {
        return getPixelValue(element, 'borderTopWidth');
      },

      'border-bottom': function(element) {
        return getPixelValue(element, 'borderBottomWidth');
      },

      'border-left': function(element) {
        return getPixelValue(element, 'borderLeftWidth');
      },

      'border-right': function(element) {
        return getPixelValue(element, 'borderRightWidth');
      },

      'margin-top': function(element) {
        return getPixelValue(element, 'marginTop');
      },

      'margin-bottom': function(element) {
        return getPixelValue(element, 'marginBottom');
      },

      'margin-left': function(element) {
        return getPixelValue(element, 'marginLeft');
      },

      'margin-right': function(element) {
        return getPixelValue(element, 'marginRight');
      }
    }
  });

  if ('getBoundingClientRect' in document.documentElement) {
    Object.extend(Element.Layout.COMPUTATIONS, {
      'right': function(element) {
        var parent = hasLayout(element.getOffsetParent());
        var rect = element.getBoundingClientRect(),
         pRect = parent.getBoundingClientRect();

        return (pRect.right - rect.right).round();
      },

      'bottom': function(element) {
        var parent = hasLayout(element.getOffsetParent());
        var rect = element.getBoundingClientRect(),
         pRect = parent.getBoundingClientRect();

        return (pRect.bottom - rect.bottom).round();
      }
    });
  }

  Element.Offset = Class.create({
    initialize: function(left, top) {
      this.left = left.round();
      this.top  = top.round();

      this[0] = this.left;
      this[1] = this.top;
    },

    relativeTo: function(offset) {
      return new Element.Offset(
        this.left - offset.left,
        this.top  - offset.top
      );
    },

    inspect: function() {
      return "#<Element.Offset left: #{left} top: #{top}>".interpolate(this);
    },

    toString: function() {
      return "[#{left}, #{top}]".interpolate(this);
    },

    toArray: function() {
      return [this.left, this.top];
    }
  });

  function getLayout(element, preCompute) {
    return new Element.Layout(element, preCompute);
  }

  function measure(element, property) {
    return $(element).getLayout().get(property);
  }

  function getDimensions(element) {
    element = $(element);
    var display = Element.getStyle(element, 'display');

    if (display && display !== 'none') {
      return { width: element.offsetWidth, height: element.offsetHeight };
    }

    var style = element.style;
    var originalStyles = {
      visibility: style.visibility,
      position:   style.position,
      display:    style.display
    };

    var newStyles = {
      visibility: 'hidden',
      display:    'block'
    };

    if (originalStyles.position !== 'fixed')
      newStyles.position = 'absolute';

    Element.setStyle(element, newStyles);

    var dimensions = {
      width:  element.offsetWidth,
      height: element.offsetHeight
    };

    Element.setStyle(element, originalStyles);

    return dimensions;
  }

  function getOffsetParent(element) {
    element = $(element);

    if (isDocument(element) || isDetached(element) || isBody(element) || isHtml(element))
      return $(document.body);

    var isInline = (Element.getStyle(element, 'display') === 'inline');
    if (!isInline && element.offsetParent) return $(element.offsetParent);

    while ((element = element.parentNode) && element !== document.body) {
      if (Element.getStyle(element, 'position') !== 'static') {
        return isHtml(element) ? $(document.body) : $(element);
      }
    }

    return $(document.body);
  }


  function cumulativeOffset(element) {
    element = $(element);
    var valueT = 0, valueL = 0;
    if (element.parentNode) {
      do {
        valueT += element.offsetTop  || 0;
        valueL += element.offsetLeft || 0;
        element = element.offsetParent;
      } while (element);
    }
    return new Element.Offset(valueL, valueT);
  }

  function positionedOffset(element) {
    element = $(element);

    var layout = element.getLayout();

    var valueT = 0, valueL = 0;
    do {
      valueT += element.offsetTop  || 0;
      valueL += element.offsetLeft || 0;
      element = element.offsetParent;
      if (element) {
        if (isBody(element)) break;
        var p = Element.getStyle(element, 'position');
        if (p !== 'static') break;
      }
    } while (element);

    valueL -= layout.get('margin-top');
    valueT -= layout.get('margin-left');

    return new Element.Offset(valueL, valueT);
  }

  function cumulativeScrollOffset(element) {
    var valueT = 0, valueL = 0;
    do {
      valueT += element.scrollTop  || 0;
      valueL += element.scrollLeft || 0;
      element = element.parentNode;
    } while (element);
    return new Element.Offset(valueL, valueT);
  }

  function viewportOffset(forElement) {
    element = $(element);
    var valueT = 0, valueL = 0, docBody = document.body;

    var element = forElement;
    do {
      valueT += element.offsetTop  || 0;
      valueL += element.offsetLeft || 0;
      if (element.offsetParent == docBody &&
        Element.getStyle(element, 'position') == 'absolute') break;
    } while (element = element.offsetParent);

    element = forElement;
    do {
      if (element != docBody) {
        valueT -= element.scrollTop  || 0;
        valueL -= element.scrollLeft || 0;
      }
    } while (element = element.parentNode);
    return new Element.Offset(valueL, valueT);
  }

  function absolutize(element) {
    element = $(element);

    if (Element.getStyle(element, 'position') === 'absolute') {
      return element;
    }

    var offsetParent = getOffsetParent(element);
    var eOffset = element.viewportOffset(),
     pOffset = offsetParent.viewportOffset();

    var offset = eOffset.relativeTo(pOffset);
    var layout = element.getLayout();

    element.store('prototype_absolutize_original_styles', {
      left:   element.getStyle('left'),
      top:    element.getStyle('top'),
      width:  element.getStyle('width'),
      height: element.getStyle('height')
    });

    element.setStyle({
      position: 'absolute',
      top:    offset.top + 'px',
      left:   offset.left + 'px',
      width:  layout.get('width') + 'px',
      height: layout.get('height') + 'px'
    });

    return element;
  }

  function relativize(element) {
    element = $(element);
    if (Element.getStyle(element, 'position') === 'relative') {
      return element;
    }

    var originalStyles =
     element.retrieve('prototype_absolutize_original_styles');

    if (originalStyles) element.setStyle(originalStyles);
    return element;
  }

  if (Prototype.Browser.IE) {
    getOffsetParent = getOffsetParent.wrap(
      function(proceed, element) {
        element = $(element);

        if (isDocument(element) || isDetached(element) || isBody(element) || isHtml(element))
          return $(document.body);

        var position = element.getStyle('position');
        if (position !== 'static') return proceed(element);

        element.setStyle({ position: 'relative' });
        var value = proceed(element);
        element.setStyle({ position: position });
        return value;
      }
    );

    positionedOffset = positionedOffset.wrap(function(proceed, element) {
      element = $(element);
      if (!element.parentNode) return new Element.Offset(0, 0);
      var position = element.getStyle('position');
      if (position !== 'static') return proceed(element);

      var offsetParent = element.getOffsetParent();
      if (offsetParent && offsetParent.getStyle('position') === 'fixed')
        hasLayout(offsetParent);

      element.setStyle({ position: 'relative' });
      var value = proceed(element);
      element.setStyle({ position: position });
      return value;
    });
  } else if (Prototype.Browser.Webkit) {
    cumulativeOffset = function(element) {
      element = $(element);
      var valueT = 0, valueL = 0;
      do {
        valueT += element.offsetTop  || 0;
        valueL += element.offsetLeft || 0;
        if (element.offsetParent == document.body)
          if (Element.getStyle(element, 'position') == 'absolute') break;

        element = element.offsetParent;
      } while (element);

      return new Element.Offset(valueL, valueT);
    };
  }


  Element.addMethods({
    getLayout:              getLayout,
    measure:                measure,
    getDimensions:          getDimensions,
    getOffsetParent:        getOffsetParent,
    cumulativeOffset:       cumulativeOffset,
    positionedOffset:       positionedOffset,
    cumulativeScrollOffset: cumulativeScrollOffset,
    viewportOffset:         viewportOffset,
    absolutize:             absolutize,
    relativize:             relativize
  });

  function isBody(element) {
    return element.nodeName.toUpperCase() === 'BODY';
  }

  function isHtml(element) {
    return element.nodeName.toUpperCase() === 'HTML';
  }

  function isDocument(element) {
    return element.nodeType === Node.DOCUMENT_NODE;
  }

  function isDetached(element) {
    return element !== document.body &&
     !Element.descendantOf(element, document.body);
  }

  if ('getBoundingClientRect' in document.documentElement) {
    Element.addMethods({
      viewportOffset: function(element) {
        element = $(element);
        if (isDetached(element)) return new Element.Offset(0, 0);

        var rect = element.getBoundingClientRect(),
         docEl = document.documentElement;
        return new Element.Offset(rect.left - docEl.clientLeft,
         rect.top - docEl.clientTop);
      }
    });
  }
})();
window.$$ = function() {
  var expression = $A(arguments).join(', ');
  return Prototype.Selector.select(expression, document);
};

Prototype.Selector = (function() {

  function select() {
    throw new Error('Method "Prototype.Selector.select" must be defined.');
  }

  function match() {
    throw new Error('Method "Prototype.Selector.match" must be defined.');
  }

  function find(elements, expression, index) {
    index = index || 0;
    var match = Prototype.Selector.match, length = elements.length, matchIndex = 0, i;

    for (i = 0; i < length; i++) {
      if (match(elements[i], expression) && index == matchIndex++) {
        return Element.extend(elements[i]);
      }
    }
  }

  function extendElements(elements) {
    for (var i = 0, length = elements.length; i < length; i++) {
      Element.extend(elements[i]);
    }
    return elements;
  }


  var K = Prototype.K;

  return {
    select: select,
    match: match,
    find: find,
    extendElements: (Element.extend === K) ? K : extendElements,
    extendElement: Element.extend
  };
})();
/*!
 * Sizzle CSS Selector Engine - v1.0
 *  Copyright 2009, The Dojo Foundation
 *  Released under the MIT, BSD, and GPL Licenses.
 *  More information: http://sizzlejs.com/
 */
(function(){

var chunker = /((?:\((?:\([^()]+\)|[^()]+)+\)|\[(?:\[[^[\]]*\]|['"][^'"]*['"]|[^[\]'"]+)+\]|\\.|[^ >+~,(\[\\]+)+|[>+~])(\s*,\s*)?((?:.|\r|\n)*)/g,
	done = 0,
	toString = Object.prototype.toString,
	hasDuplicate = false,
	baseHasDuplicate = true;

[0, 0].sort(function(){
	baseHasDuplicate = false;
	return 0;
});

var Sizzle = function(selector, context, results, seed) {
	results = results || [];
	var origContext = context = context || document;

	if ( context.nodeType !== 1 && context.nodeType !== 9 ) {
		return [];
	}

	if ( !selector || typeof selector !== "string" ) {
		return results;
	}

	var parts = [], m, set, checkSet, check, mode, extra, prune = true, contextXML = isXML(context),
		soFar = selector;

	while ( (chunker.exec(""), m = chunker.exec(soFar)) !== null ) {
		soFar = m[3];

		parts.push( m[1] );

		if ( m[2] ) {
			extra = m[3];
			break;
		}
	}

	if ( parts.length > 1 && origPOS.exec( selector ) ) {
		if ( parts.length === 2 && Expr.relative[ parts[0] ] ) {
			set = posProcess( parts[0] + parts[1], context );
		} else {
			set = Expr.relative[ parts[0] ] ?
				[ context ] :
				Sizzle( parts.shift(), context );

			while ( parts.length ) {
				selector = parts.shift();

				if ( Expr.relative[ selector ] )
					selector += parts.shift();

				set = posProcess( selector, set );
			}
		}
	} else {
		if ( !seed && parts.length > 1 && context.nodeType === 9 && !contextXML &&
				Expr.match.ID.test(parts[0]) && !Expr.match.ID.test(parts[parts.length - 1]) ) {
			var ret = Sizzle.find( parts.shift(), context, contextXML );
			context = ret.expr ? Sizzle.filter( ret.expr, ret.set )[0] : ret.set[0];
		}

		if ( context ) {
			var ret = seed ?
				{ expr: parts.pop(), set: makeArray(seed) } :
				Sizzle.find( parts.pop(), parts.length === 1 && (parts[0] === "~" || parts[0] === "+") && context.parentNode ? context.parentNode : context, contextXML );
			set = ret.expr ? Sizzle.filter( ret.expr, ret.set ) : ret.set;

			if ( parts.length > 0 ) {
				checkSet = makeArray(set);
			} else {
				prune = false;
			}

			while ( parts.length ) {
				var cur = parts.pop(), pop = cur;

				if ( !Expr.relative[ cur ] ) {
					cur = "";
				} else {
					pop = parts.pop();
				}

				if ( pop == null ) {
					pop = context;
				}

				Expr.relative[ cur ]( checkSet, pop, contextXML );
			}
		} else {
			checkSet = parts = [];
		}
	}

	if ( !checkSet ) {
		checkSet = set;
	}

	if ( !checkSet ) {
		throw "Syntax error, unrecognized expression: " + (cur || selector);
	}

	if ( toString.call(checkSet) === "[object Array]" ) {
		if ( !prune ) {
			results.push.apply( results, checkSet );
		} else if ( context && context.nodeType === 1 ) {
			for ( var i = 0; checkSet[i] != null; i++ ) {
				if ( checkSet[i] && (checkSet[i] === true || checkSet[i].nodeType === 1 && contains(context, checkSet[i])) ) {
					results.push( set[i] );
				}
			}
		} else {
			for ( var i = 0; checkSet[i] != null; i++ ) {
				if ( checkSet[i] && checkSet[i].nodeType === 1 ) {
					results.push( set[i] );
				}
			}
		}
	} else {
		makeArray( checkSet, results );
	}

	if ( extra ) {
		Sizzle( extra, origContext, results, seed );
		Sizzle.uniqueSort( results );
	}

	return results;
};

Sizzle.uniqueSort = function(results){
	if ( sortOrder ) {
		hasDuplicate = baseHasDuplicate;
		results.sort(sortOrder);

		if ( hasDuplicate ) {
			for ( var i = 1; i < results.length; i++ ) {
				if ( results[i] === results[i-1] ) {
					results.splice(i--, 1);
				}
			}
		}
	}

	return results;
};

Sizzle.matches = function(expr, set){
	return Sizzle(expr, null, null, set);
};

Sizzle.find = function(expr, context, isXML){
	var set, match;

	if ( !expr ) {
		return [];
	}

	for ( var i = 0, l = Expr.order.length; i < l; i++ ) {
		var type = Expr.order[i], match;

		if ( (match = Expr.leftMatch[ type ].exec( expr )) ) {
			var left = match[1];
			match.splice(1,1);

			if ( left.substr( left.length - 1 ) !== "\\" ) {
				match[1] = (match[1] || "").replace(/\\/g, "");
				set = Expr.find[ type ]( match, context, isXML );
				if ( set != null ) {
					expr = expr.replace( Expr.match[ type ], "" );
					break;
				}
			}
		}
	}

	if ( !set ) {
		set = context.getElementsByTagName("*");
	}

	return {set: set, expr: expr};
};

Sizzle.filter = function(expr, set, inplace, not){
	var old = expr, result = [], curLoop = set, match, anyFound,
		isXMLFilter = set && set[0] && isXML(set[0]);

	while ( expr && set.length ) {
		for ( var type in Expr.filter ) {
			if ( (match = Expr.match[ type ].exec( expr )) != null ) {
				var filter = Expr.filter[ type ], found, item;
				anyFound = false;

				if ( curLoop == result ) {
					result = [];
				}

				if ( Expr.preFilter[ type ] ) {
					match = Expr.preFilter[ type ]( match, curLoop, inplace, result, not, isXMLFilter );

					if ( !match ) {
						anyFound = found = true;
					} else if ( match === true ) {
						continue;
					}
				}

				if ( match ) {
					for ( var i = 0; (item = curLoop[i]) != null; i++ ) {
						if ( item ) {
							found = filter( item, match, i, curLoop );
							var pass = not ^ !!found;

							if ( inplace && found != null ) {
								if ( pass ) {
									anyFound = true;
								} else {
									curLoop[i] = false;
								}
							} else if ( pass ) {
								result.push( item );
								anyFound = true;
							}
						}
					}
				}

				if ( found !== undefined ) {
					if ( !inplace ) {
						curLoop = result;
					}

					expr = expr.replace( Expr.match[ type ], "" );

					if ( !anyFound ) {
						return [];
					}

					break;
				}
			}
		}

		if ( expr == old ) {
			if ( anyFound == null ) {
				throw "Syntax error, unrecognized expression: " + expr;
			} else {
				break;
			}
		}

		old = expr;
	}

	return curLoop;
};

var Expr = Sizzle.selectors = {
	order: [ "ID", "NAME", "TAG" ],
	match: {
		ID: /#((?:[\w\u00c0-\uFFFF-]|\\.)+)/,
		CLASS: /\.((?:[\w\u00c0-\uFFFF-]|\\.)+)/,
		NAME: /\[name=['"]*((?:[\w\u00c0-\uFFFF-]|\\.)+)['"]*\]/,
		ATTR: /\[\s*((?:[\w\u00c0-\uFFFF-]|\\.)+)\s*(?:(\S?=)\s*(['"]*)(.*?)\3|)\s*\]/,
		TAG: /^((?:[\w\u00c0-\uFFFF\*-]|\\.)+)/,
		CHILD: /:(only|nth|last|first)-child(?:\((even|odd|[\dn+-]*)\))?/,
		POS: /:(nth|eq|gt|lt|first|last|even|odd)(?:\((\d*)\))?(?=[^-]|$)/,
		PSEUDO: /:((?:[\w\u00c0-\uFFFF-]|\\.)+)(?:\((['"]*)((?:\([^\)]+\)|[^\2\(\)]*)+)\2\))?/
	},
	leftMatch: {},
	attrMap: {
		"class": "className",
		"for": "htmlFor"
	},
	attrHandle: {
		href: function(elem){
			return elem.getAttribute("href");
		}
	},
	relative: {
		"+": function(checkSet, part, isXML){
			var isPartStr = typeof part === "string",
				isTag = isPartStr && !/\W/.test(part),
				isPartStrNotTag = isPartStr && !isTag;

			if ( isTag && !isXML ) {
				part = part.toUpperCase();
			}

			for ( var i = 0, l = checkSet.length, elem; i < l; i++ ) {
				if ( (elem = checkSet[i]) ) {
					while ( (elem = elem.previousSibling) && elem.nodeType !== 1 ) {}

					checkSet[i] = isPartStrNotTag || elem && elem.nodeName === part ?
						elem || false :
						elem === part;
				}
			}

			if ( isPartStrNotTag ) {
				Sizzle.filter( part, checkSet, true );
			}
		},
		">": function(checkSet, part, isXML){
			var isPartStr = typeof part === "string";

			if ( isPartStr && !/\W/.test(part) ) {
				part = isXML ? part : part.toUpperCase();

				for ( var i = 0, l = checkSet.length; i < l; i++ ) {
					var elem = checkSet[i];
					if ( elem ) {
						var parent = elem.parentNode;
						checkSet[i] = parent.nodeName === part ? parent : false;
					}
				}
			} else {
				for ( var i = 0, l = checkSet.length; i < l; i++ ) {
					var elem = checkSet[i];
					if ( elem ) {
						checkSet[i] = isPartStr ?
							elem.parentNode :
							elem.parentNode === part;
					}
				}

				if ( isPartStr ) {
					Sizzle.filter( part, checkSet, true );
				}
			}
		},
		"": function(checkSet, part, isXML){
			var doneName = done++, checkFn = dirCheck;

			if ( !/\W/.test(part) ) {
				var nodeCheck = part = isXML ? part : part.toUpperCase();
				checkFn = dirNodeCheck;
			}

			checkFn("parentNode", part, doneName, checkSet, nodeCheck, isXML);
		},
		"~": function(checkSet, part, isXML){
			var doneName = done++, checkFn = dirCheck;

			if ( typeof part === "string" && !/\W/.test(part) ) {
				var nodeCheck = part = isXML ? part : part.toUpperCase();
				checkFn = dirNodeCheck;
			}

			checkFn("previousSibling", part, doneName, checkSet, nodeCheck, isXML);
		}
	},
	find: {
		ID: function(match, context, isXML){
			if ( typeof context.getElementById !== "undefined" && !isXML ) {
				var m = context.getElementById(match[1]);
				return m ? [m] : [];
			}
		},
		NAME: function(match, context, isXML){
			if ( typeof context.getElementsByName !== "undefined" ) {
				var ret = [], results = context.getElementsByName(match[1]);

				for ( var i = 0, l = results.length; i < l; i++ ) {
					if ( results[i].getAttribute("name") === match[1] ) {
						ret.push( results[i] );
					}
				}

				return ret.length === 0 ? null : ret;
			}
		},
		TAG: function(match, context){
			return context.getElementsByTagName(match[1]);
		}
	},
	preFilter: {
		CLASS: function(match, curLoop, inplace, result, not, isXML){
			match = " " + match[1].replace(/\\/g, "") + " ";

			if ( isXML ) {
				return match;
			}

			for ( var i = 0, elem; (elem = curLoop[i]) != null; i++ ) {
				if ( elem ) {
					if ( not ^ (elem.className && (" " + elem.className + " ").indexOf(match) >= 0) ) {
						if ( !inplace )
							result.push( elem );
					} else if ( inplace ) {
						curLoop[i] = false;
					}
				}
			}

			return false;
		},
		ID: function(match){
			return match[1].replace(/\\/g, "");
		},
		TAG: function(match, curLoop){
			for ( var i = 0; curLoop[i] === false; i++ ){}
			return curLoop[i] && isXML(curLoop[i]) ? match[1] : match[1].toUpperCase();
		},
		CHILD: function(match){
			if ( match[1] == "nth" ) {
				var test = /(-?)(\d*)n((?:\+|-)?\d*)/.exec(
					match[2] == "even" && "2n" || match[2] == "odd" && "2n+1" ||
					!/\D/.test( match[2] ) && "0n+" + match[2] || match[2]);

				match[2] = (test[1] + (test[2] || 1)) - 0;
				match[3] = test[3] - 0;
			}

			match[0] = done++;

			return match;
		},
		ATTR: function(match, curLoop, inplace, result, not, isXML){
			var name = match[1].replace(/\\/g, "");

			if ( !isXML && Expr.attrMap[name] ) {
				match[1] = Expr.attrMap[name];
			}

			if ( match[2] === "~=" ) {
				match[4] = " " + match[4] + " ";
			}

			return match;
		},
		PSEUDO: function(match, curLoop, inplace, result, not){
			if ( match[1] === "not" ) {
				if ( ( chunker.exec(match[3]) || "" ).length > 1 || /^\w/.test(match[3]) ) {
					match[3] = Sizzle(match[3], null, null, curLoop);
				} else {
					var ret = Sizzle.filter(match[3], curLoop, inplace, true ^ not);
					if ( !inplace ) {
						result.push.apply( result, ret );
					}
					return false;
				}
			} else if ( Expr.match.POS.test( match[0] ) || Expr.match.CHILD.test( match[0] ) ) {
				return true;
			}

			return match;
		},
		POS: function(match){
			match.unshift( true );
			return match;
		}
	},
	filters: {
		enabled: function(elem){
			return elem.disabled === false && elem.type !== "hidden";
		},
		disabled: function(elem){
			return elem.disabled === true;
		},
		checked: function(elem){
			return elem.checked === true;
		},
		selected: function(elem){
			elem.parentNode.selectedIndex;
			return elem.selected === true;
		},
		parent: function(elem){
			return !!elem.firstChild;
		},
		empty: function(elem){
			return !elem.firstChild;
		},
		has: function(elem, i, match){
			return !!Sizzle( match[3], elem ).length;
		},
		header: function(elem){
			return /h\d/i.test( elem.nodeName );
		},
		text: function(elem){
			return "text" === elem.type;
		},
		radio: function(elem){
			return "radio" === elem.type;
		},
		checkbox: function(elem){
			return "checkbox" === elem.type;
		},
		file: function(elem){
			return "file" === elem.type;
		},
		password: function(elem){
			return "password" === elem.type;
		},
		submit: function(elem){
			return "submit" === elem.type;
		},
		image: function(elem){
			return "image" === elem.type;
		},
		reset: function(elem){
			return "reset" === elem.type;
		},
		button: function(elem){
			return "button" === elem.type || elem.nodeName.toUpperCase() === "BUTTON";
		},
		input: function(elem){
			return /input|select|textarea|button/i.test(elem.nodeName);
		}
	},
	setFilters: {
		first: function(elem, i){
			return i === 0;
		},
		last: function(elem, i, match, array){
			return i === array.length - 1;
		},
		even: function(elem, i){
			return i % 2 === 0;
		},
		odd: function(elem, i){
			return i % 2 === 1;
		},
		lt: function(elem, i, match){
			return i < match[3] - 0;
		},
		gt: function(elem, i, match){
			return i > match[3] - 0;
		},
		nth: function(elem, i, match){
			return match[3] - 0 == i;
		},
		eq: function(elem, i, match){
			return match[3] - 0 == i;
		}
	},
	filter: {
		PSEUDO: function(elem, match, i, array){
			var name = match[1], filter = Expr.filters[ name ];

			if ( filter ) {
				return filter( elem, i, match, array );
			} else if ( name === "contains" ) {
				return (elem.textContent || elem.innerText || "").indexOf(match[3]) >= 0;
			} else if ( name === "not" ) {
				var not = match[3];

				for ( var i = 0, l = not.length; i < l; i++ ) {
					if ( not[i] === elem ) {
						return false;
					}
				}

				return true;
			}
		},
		CHILD: function(elem, match){
			var type = match[1], node = elem;
			switch (type) {
				case 'only':
				case 'first':
					while ( (node = node.previousSibling) )  {
						if ( node.nodeType === 1 ) return false;
					}
					if ( type == 'first') return true;
					node = elem;
				case 'last':
					while ( (node = node.nextSibling) )  {
						if ( node.nodeType === 1 ) return false;
					}
					return true;
				case 'nth':
					var first = match[2], last = match[3];

					if ( first == 1 && last == 0 ) {
						return true;
					}

					var doneName = match[0],
						parent = elem.parentNode;

					if ( parent && (parent.sizcache !== doneName || !elem.nodeIndex) ) {
						var count = 0;
						for ( node = parent.firstChild; node; node = node.nextSibling ) {
							if ( node.nodeType === 1 ) {
								node.nodeIndex = ++count;
							}
						}
						parent.sizcache = doneName;
					}

					var diff = elem.nodeIndex - last;
					if ( first == 0 ) {
						return diff == 0;
					} else {
						return ( diff % first == 0 && diff / first >= 0 );
					}
			}
		},
		ID: function(elem, match){
			return elem.nodeType === 1 && elem.getAttribute("id") === match;
		},
		TAG: function(elem, match){
			return (match === "*" && elem.nodeType === 1) || elem.nodeName === match;
		},
		CLASS: function(elem, match){
			return (" " + (elem.className || elem.getAttribute("class")) + " ")
				.indexOf( match ) > -1;
		},
		ATTR: function(elem, match){
			var name = match[1],
				result = Expr.attrHandle[ name ] ?
					Expr.attrHandle[ name ]( elem ) :
					elem[ name ] != null ?
						elem[ name ] :
						elem.getAttribute( name ),
				value = result + "",
				type = match[2],
				check = match[4];

			return result == null ?
				type === "!=" :
				type === "=" ?
				value === check :
				type === "*=" ?
				value.indexOf(check) >= 0 :
				type === "~=" ?
				(" " + value + " ").indexOf(check) >= 0 :
				!check ?
				value && result !== false :
				type === "!=" ?
				value != check :
				type === "^=" ?
				value.indexOf(check) === 0 :
				type === "$=" ?
				value.substr(value.length - check.length) === check :
				type === "|=" ?
				value === check || value.substr(0, check.length + 1) === check + "-" :
				false;
		},
		POS: function(elem, match, i, array){
			var name = match[2], filter = Expr.setFilters[ name ];

			if ( filter ) {
				return filter( elem, i, match, array );
			}
		}
	}
};

var origPOS = Expr.match.POS;

for ( var type in Expr.match ) {
	Expr.match[ type ] = new RegExp( Expr.match[ type ].source + /(?![^\[]*\])(?![^\(]*\))/.source );
	Expr.leftMatch[ type ] = new RegExp( /(^(?:.|\r|\n)*?)/.source + Expr.match[ type ].source );
}

var makeArray = function(array, results) {
	array = Array.prototype.slice.call( array, 0 );

	if ( results ) {
		results.push.apply( results, array );
		return results;
	}

	return array;
};

try {
	Array.prototype.slice.call( document.documentElement.childNodes, 0 );

} catch(e){
	makeArray = function(array, results) {
		var ret = results || [];

		if ( toString.call(array) === "[object Array]" ) {
			Array.prototype.push.apply( ret, array );
		} else {
			if ( typeof array.length === "number" ) {
				for ( var i = 0, l = array.length; i < l; i++ ) {
					ret.push( array[i] );
				}
			} else {
				for ( var i = 0; array[i]; i++ ) {
					ret.push( array[i] );
				}
			}
		}

		return ret;
	};
}

var sortOrder;

if ( document.documentElement.compareDocumentPosition ) {
	sortOrder = function( a, b ) {
		if ( !a.compareDocumentPosition || !b.compareDocumentPosition ) {
			if ( a == b ) {
				hasDuplicate = true;
			}
			return 0;
		}

		var ret = a.compareDocumentPosition(b) & 4 ? -1 : a === b ? 0 : 1;
		if ( ret === 0 ) {
			hasDuplicate = true;
		}
		return ret;
	};
} else if ( "sourceIndex" in document.documentElement ) {
	sortOrder = function( a, b ) {
		if ( !a.sourceIndex || !b.sourceIndex ) {
			if ( a == b ) {
				hasDuplicate = true;
			}
			return 0;
		}

		var ret = a.sourceIndex - b.sourceIndex;
		if ( ret === 0 ) {
			hasDuplicate = true;
		}
		return ret;
	};
} else if ( document.createRange ) {
	sortOrder = function( a, b ) {
		if ( !a.ownerDocument || !b.ownerDocument ) {
			if ( a == b ) {
				hasDuplicate = true;
			}
			return 0;
		}

		var aRange = a.ownerDocument.createRange(), bRange = b.ownerDocument.createRange();
		aRange.setStart(a, 0);
		aRange.setEnd(a, 0);
		bRange.setStart(b, 0);
		bRange.setEnd(b, 0);
		var ret = aRange.compareBoundaryPoints(Range.START_TO_END, bRange);
		if ( ret === 0 ) {
			hasDuplicate = true;
		}
		return ret;
	};
}

(function(){
	var form = document.createElement("div"),
		id = "script" + (new Date).getTime();
	form.innerHTML = "<a name='" + id + "'/>";

	var root = document.documentElement;
	root.insertBefore( form, root.firstChild );

	if ( !!document.getElementById( id ) ) {
		Expr.find.ID = function(match, context, isXML){
			if ( typeof context.getElementById !== "undefined" && !isXML ) {
				var m = context.getElementById(match[1]);
				return m ? m.id === match[1] || typeof m.getAttributeNode !== "undefined" && m.getAttributeNode("id").nodeValue === match[1] ? [m] : undefined : [];
			}
		};

		Expr.filter.ID = function(elem, match){
			var node = typeof elem.getAttributeNode !== "undefined" && elem.getAttributeNode("id");
			return elem.nodeType === 1 && node && node.nodeValue === match;
		};
	}

	root.removeChild( form );
	root = form = null; // release memory in IE
})();

(function(){

	var div = document.createElement("div");
	div.appendChild( document.createComment("") );

	if ( div.getElementsByTagName("*").length > 0 ) {
		Expr.find.TAG = function(match, context){
			var results = context.getElementsByTagName(match[1]);

			if ( match[1] === "*" ) {
				var tmp = [];

				for ( var i = 0; results[i]; i++ ) {
					if ( results[i].nodeType === 1 ) {
						tmp.push( results[i] );
					}
				}

				results = tmp;
			}

			return results;
		};
	}

	div.innerHTML = "<a href='#'></a>";
	if ( div.firstChild && typeof div.firstChild.getAttribute !== "undefined" &&
			div.firstChild.getAttribute("href") !== "#" ) {
		Expr.attrHandle.href = function(elem){
			return elem.getAttribute("href", 2);
		};
	}

	div = null; // release memory in IE
})();

if ( document.querySelectorAll ) (function(){
	var oldSizzle = Sizzle, div = document.createElement("div");
	div.innerHTML = "<p class='TEST'></p>";

	if ( div.querySelectorAll && div.querySelectorAll(".TEST").length === 0 ) {
		return;
	}

	Sizzle = function(query, context, extra, seed){
		context = context || document;

		if ( !seed && context.nodeType === 9 && !isXML(context) ) {
			try {
				return makeArray( context.querySelectorAll(query), extra );
			} catch(e){}
		}

		return oldSizzle(query, context, extra, seed);
	};

	for ( var prop in oldSizzle ) {
		Sizzle[ prop ] = oldSizzle[ prop ];
	}

	div = null; // release memory in IE
})();

if ( document.getElementsByClassName && document.documentElement.getElementsByClassName ) (function(){
	var div = document.createElement("div");
	div.innerHTML = "<div class='test e'></div><div class='test'></div>";

	if ( div.getElementsByClassName("e").length === 0 )
		return;

	div.lastChild.className = "e";

	if ( div.getElementsByClassName("e").length === 1 )
		return;

	Expr.order.splice(1, 0, "CLASS");
	Expr.find.CLASS = function(match, context, isXML) {
		if ( typeof context.getElementsByClassName !== "undefined" && !isXML ) {
			return context.getElementsByClassName(match[1]);
		}
	};

	div = null; // release memory in IE
})();

function dirNodeCheck( dir, cur, doneName, checkSet, nodeCheck, isXML ) {
	var sibDir = dir == "previousSibling" && !isXML;
	for ( var i = 0, l = checkSet.length; i < l; i++ ) {
		var elem = checkSet[i];
		if ( elem ) {
			if ( sibDir && elem.nodeType === 1 ){
				elem.sizcache = doneName;
				elem.sizset = i;
			}
			elem = elem[dir];
			var match = false;

			while ( elem ) {
				if ( elem.sizcache === doneName ) {
					match = checkSet[elem.sizset];
					break;
				}

				if ( elem.nodeType === 1 && !isXML ){
					elem.sizcache = doneName;
					elem.sizset = i;
				}

				if ( elem.nodeName === cur ) {
					match = elem;
					break;
				}

				elem = elem[dir];
			}

			checkSet[i] = match;
		}
	}
}

function dirCheck( dir, cur, doneName, checkSet, nodeCheck, isXML ) {
	var sibDir = dir == "previousSibling" && !isXML;
	for ( var i = 0, l = checkSet.length; i < l; i++ ) {
		var elem = checkSet[i];
		if ( elem ) {
			if ( sibDir && elem.nodeType === 1 ) {
				elem.sizcache = doneName;
				elem.sizset = i;
			}
			elem = elem[dir];
			var match = false;

			while ( elem ) {
				if ( elem.sizcache === doneName ) {
					match = checkSet[elem.sizset];
					break;
				}

				if ( elem.nodeType === 1 ) {
					if ( !isXML ) {
						elem.sizcache = doneName;
						elem.sizset = i;
					}
					if ( typeof cur !== "string" ) {
						if ( elem === cur ) {
							match = true;
							break;
						}

					} else if ( Sizzle.filter( cur, [elem] ).length > 0 ) {
						match = elem;
						break;
					}
				}

				elem = elem[dir];
			}

			checkSet[i] = match;
		}
	}
}

var contains = document.compareDocumentPosition ?  function(a, b){
	return a.compareDocumentPosition(b) & 16;
} : function(a, b){
	return a !== b && (a.contains ? a.contains(b) : true);
};

var isXML = function(elem){
	return elem.nodeType === 9 && elem.documentElement.nodeName !== "HTML" ||
		!!elem.ownerDocument && elem.ownerDocument.documentElement.nodeName !== "HTML";
};

var posProcess = function(selector, context){
	var tmpSet = [], later = "", match,
		root = context.nodeType ? [context] : context;

	while ( (match = Expr.match.PSEUDO.exec( selector )) ) {
		later += match[0];
		selector = selector.replace( Expr.match.PSEUDO, "" );
	}

	selector = Expr.relative[selector] ? selector + "*" : selector;

	for ( var i = 0, l = root.length; i < l; i++ ) {
		Sizzle( selector, root[i], tmpSet );
	}

	return Sizzle.filter( later, tmpSet );
};


window.Sizzle = Sizzle;

})();

Prototype._original_property = window.Sizzle;

;(function(engine) {
  var extendElements = Prototype.Selector.extendElements;

  function select(selector, scope) {
    return extendElements(engine(selector, scope || document));
  }

  function match(element, selector) {
    return engine.matches(selector, [element]).length == 1;
  }

  Prototype.Selector.engine = engine;
  Prototype.Selector.select = select;
  Prototype.Selector.match = match;
})(Sizzle);

window.Sizzle = Prototype._original_property;
delete Prototype._original_property;

var Form = {
  reset: function(form) {
    form = $(form);
    form.reset();
    return form;
  },

  serializeElements: function(elements, options) {
    if (typeof options != 'object') options = { hash: !!options };
    else if (Object.isUndefined(options.hash)) options.hash = true;
    var key, value, submitted = false, submit = options.submit, accumulator, initial;

    if (options.hash) {
      initial = {};
      accumulator = function(result, key, value) {
        if (key in result) {
          if (!Object.isArray(result[key])) result[key] = [result[key]];
          result[key].push(value);
        } else result[key] = value;
        return result;
      };
    } else {
      initial = '';
      accumulator = function(result, key, value) {
        return result + (result ? '&' : '') + encodeURIComponent(key) + '=' + encodeURIComponent(value);
      }
    }

    return elements.inject(initial, function(result, element) {
      if (!element.disabled && element.name) {
        key = element.name; value = $(element).getValue();
        if (value != null && element.type != 'file' && (element.type != 'submit' || (!submitted &&
            submit !== false && (!submit || key == submit) && (submitted = true)))) {
          result = accumulator(result, key, value);
        }
      }
      return result;
    });
  }
};

Form.Methods = {
  serialize: function(form, options) {
    return Form.serializeElements(Form.getElements(form), options);
  },

  getElements: function(form) {
    var elements = $(form).getElementsByTagName('*'),
        element,
        arr = [ ],
        serializers = Form.Element.Serializers;
    for (var i = 0; element = elements[i]; i++) {
      arr.push(element);
    }
    return arr.inject([], function(elements, child) {
      if (serializers[child.tagName.toLowerCase()])
        elements.push(Element.extend(child));
      return elements;
    })
  },

  getInputs: function(form, typeName, name) {
    form = $(form);
    var inputs = form.getElementsByTagName('input');

    if (!typeName && !name) return $A(inputs).map(Element.extend);

    for (var i = 0, matchingInputs = [], length = inputs.length; i < length; i++) {
      var input = inputs[i];
      if ((typeName && input.type != typeName) || (name && input.name != name))
        continue;
      matchingInputs.push(Element.extend(input));
    }

    return matchingInputs;
  },

  disable: function(form) {
    form = $(form);
    Form.getElements(form).invoke('disable');
    return form;
  },

  enable: function(form) {
    form = $(form);
    Form.getElements(form).invoke('enable');
    return form;
  },

  findFirstElement: function(form) {
    var elements = $(form).getElements().findAll(function(element) {
      return 'hidden' != element.type && !element.disabled;
    });
    var firstByIndex = elements.findAll(function(element) {
      return element.hasAttribute('tabIndex') && element.tabIndex >= 0;
    }).sortBy(function(element) { return element.tabIndex }).first();

    return firstByIndex ? firstByIndex : elements.find(function(element) {
      return /^(?:input|select|textarea)$/i.test(element.tagName);
    });
  },

  focusFirstElement: function(form) {
    form = $(form);
    var element = form.findFirstElement();
    if (element) element.activate();
    return form;
  },

  request: function(form, options) {
    form = $(form), options = Object.clone(options || { });

    var params = options.parameters, action = form.readAttribute('action') || '';
    if (action.blank()) action = window.location.href;
    options.parameters = form.serialize(true);

    if (params) {
      if (Object.isString(params)) params = params.toQueryParams();
      Object.extend(options.parameters, params);
    }

    if (form.hasAttribute('method') && !options.method)
      options.method = form.method;

    return new Ajax.Request(action, options);
  }
};

/*--------------------------------------------------------------------------*/


Form.Element = {
  focus: function(element) {
    $(element).focus();
    return element;
  },

  select: function(element) {
    $(element).select();
    return element;
  }
};

Form.Element.Methods = {

  serialize: function(element) {
    element = $(element);
    if (!element.disabled && element.name) {
      var value = element.getValue();
      if (value != undefined) {
        var pair = { };
        pair[element.name] = value;
        return Object.toQueryString(pair);
      }
    }
    return '';
  },

  getValue: function(element) {
    element = $(element);
    var method = element.tagName.toLowerCase();
    return Form.Element.Serializers[method](element);
  },

  setValue: function(element, value) {
    element = $(element);
    var method = element.tagName.toLowerCase();
    Form.Element.Serializers[method](element, value);
    return element;
  },

  clear: function(element) {
    $(element).value = '';
    return element;
  },

  present: function(element) {
    return $(element).value != '';
  },

  activate: function(element) {
    element = $(element);
    try {
      element.focus();
      if (element.select && (element.tagName.toLowerCase() != 'input' ||
          !(/^(?:button|reset|submit)$/i.test(element.type))))
        element.select();
    } catch (e) { }
    return element;
  },

  disable: function(element) {
    element = $(element);
    element.disabled = true;
    return element;
  },

  enable: function(element) {
    element = $(element);
    element.disabled = false;
    return element;
  }
};

/*--------------------------------------------------------------------------*/

var Field = Form.Element;

var $F = Form.Element.Methods.getValue;

/*--------------------------------------------------------------------------*/

Form.Element.Serializers = (function() {
  function input(element, value) {
    switch (element.type.toLowerCase()) {
      case 'checkbox':
      case 'radio':
        return inputSelector(element, value);
      default:
        return valueSelector(element, value);
    }
  }

  function inputSelector(element, value) {
    if (Object.isUndefined(value))
      return element.checked ? element.value : null;
    else element.checked = !!value;
  }

  function valueSelector(element, value) {
    if (Object.isUndefined(value)) return element.value;
    else element.value = value;
  }

  function select(element, value) {
    if (Object.isUndefined(value))
      return (element.type === 'select-one' ? selectOne : selectMany)(element);

    var opt, currentValue, single = !Object.isArray(value);
    for (var i = 0, length = element.length; i < length; i++) {
      opt = element.options[i];
      currentValue = this.optionValue(opt);
      if (single) {
        if (currentValue == value) {
          opt.selected = true;
          return;
        }
      }
      else opt.selected = value.include(currentValue);
    }
  }

  function selectOne(element) {
    var index = element.selectedIndex;
    return index >= 0 ? optionValue(element.options[index]) : null;
  }

  function selectMany(element) {
    var values, length = element.length;
    if (!length) return null;

    for (var i = 0, values = []; i < length; i++) {
      var opt = element.options[i];
      if (opt.selected) values.push(optionValue(opt));
    }
    return values;
  }

  function optionValue(opt) {
    return Element.hasAttribute(opt, 'value') ? opt.value : opt.text;
  }

  return {
    input:         input,
    inputSelector: inputSelector,
    textarea:      valueSelector,
    select:        select,
    selectOne:     selectOne,
    selectMany:    selectMany,
    optionValue:   optionValue,
    button:        valueSelector
  };
})();

/*--------------------------------------------------------------------------*/


Abstract.TimedObserver = Class.create(PeriodicalExecuter, {
  initialize: function($super, element, frequency, callback) {
    $super(callback, frequency);
    this.element   = $(element);
    this.lastValue = this.getValue();
  },

  execute: function() {
    var value = this.getValue();
    if (Object.isString(this.lastValue) && Object.isString(value) ?
        this.lastValue != value : String(this.lastValue) != String(value)) {
      this.callback(this.element, value);
      this.lastValue = value;
    }
  }
});

Form.Element.Observer = Class.create(Abstract.TimedObserver, {
  getValue: function() {
    return Form.Element.getValue(this.element);
  }
});

Form.Observer = Class.create(Abstract.TimedObserver, {
  getValue: function() {
    return Form.serialize(this.element);
  }
});

/*--------------------------------------------------------------------------*/

Abstract.EventObserver = Class.create({
  initialize: function(element, callback) {
    this.element  = $(element);
    this.callback = callback;

    this.lastValue = this.getValue();
    if (this.element.tagName.toLowerCase() == 'form')
      this.registerFormCallbacks();
    else
      this.registerCallback(this.element);
  },

  onElementEvent: function() {
    var value = this.getValue();
    if (this.lastValue != value) {
      this.callback(this.element, value);
      this.lastValue = value;
    }
  },

  registerFormCallbacks: function() {
    Form.getElements(this.element).each(this.registerCallback, this);
  },

  registerCallback: function(element) {
    if (element.type) {
      switch (element.type.toLowerCase()) {
        case 'checkbox':
        case 'radio':
          Event.observe(element, 'click', this.onElementEvent.bind(this));
          break;
        default:
          Event.observe(element, 'change', this.onElementEvent.bind(this));
          break;
      }
    }
  }
});

Form.Element.EventObserver = Class.create(Abstract.EventObserver, {
  getValue: function() {
    return Form.Element.getValue(this.element);
  }
});

Form.EventObserver = Class.create(Abstract.EventObserver, {
  getValue: function() {
    return Form.serialize(this.element);
  }
});
(function() {

  var Event = {
    KEY_BACKSPACE: 8,
    KEY_TAB:       9,
    KEY_RETURN:   13,
    KEY_ESC:      27,
    KEY_LEFT:     37,
    KEY_UP:       38,
    KEY_RIGHT:    39,
    KEY_DOWN:     40,
    KEY_DELETE:   46,
    KEY_HOME:     36,
    KEY_END:      35,
    KEY_PAGEUP:   33,
    KEY_PAGEDOWN: 34,
    KEY_INSERT:   45,

    cache: {}
  };

  var docEl = document.documentElement;
  var MOUSEENTER_MOUSELEAVE_EVENTS_SUPPORTED = 'onmouseenter' in docEl
    && 'onmouseleave' in docEl;



  var isIELegacyEvent = function(event) { return false; };

  if (window.attachEvent) {
    if (window.addEventListener) {
      isIELegacyEvent = function(event) {
        return !(event instanceof window.Event);
      };
    } else {
      isIELegacyEvent = function(event) { return true; };
    }
  }

  var _isButton;

  function _isButtonForDOMEvents(event, code) {
    return event.which ? (event.which === code + 1) : (event.button === code);
  }

  var legacyButtonMap = { 0: 1, 1: 4, 2: 2 };
  function _isButtonForLegacyEvents(event, code) {
    return event.button === legacyButtonMap[code];
  }

  function _isButtonForWebKit(event, code) {
    switch (code) {
      case 0: return event.which == 1 && !event.metaKey;
      case 1: return event.which == 2 || (event.which == 1 && event.metaKey);
      case 2: return event.which == 3;
      default: return false;
    }
  }

  if (window.attachEvent) {
    if (!window.addEventListener) {
      _isButton = _isButtonForLegacyEvents;
    } else {
      _isButton = function(event, code) {
        return isIELegacyEvent(event) ? _isButtonForLegacyEvents(event, code) :
         _isButtonForDOMEvents(event, code);
      }
    }
  } else if (Prototype.Browser.WebKit) {
    _isButton = _isButtonForWebKit;
  } else {
    _isButton = _isButtonForDOMEvents;
  }

  function isLeftClick(event)   { return _isButton(event, 0) }

  function isMiddleClick(event) { return _isButton(event, 1) }

  function isRightClick(event)  { return _isButton(event, 2) }

  function element(event) {
    event = Event.extend(event);

    var node = event.target, type = event.type,
     currentTarget = event.currentTarget;

    if (currentTarget && currentTarget.tagName) {
      if (type === 'load' || type === 'error' ||
        (type === 'click' && currentTarget.tagName.toLowerCase() === 'input'
          && currentTarget.type === 'radio'))
            node = currentTarget;
    }

    if (node.nodeType == Node.TEXT_NODE)
      node = node.parentNode;

    return Element.extend(node);
  }

  function findElement(event, expression) {
    var element = Event.element(event);

    if (!expression) return element;
    while (element) {
      if (Object.isElement(element) && Prototype.Selector.match(element, expression)) {
        return Element.extend(element);
      }
      element = element.parentNode;
    }
  }

  function pointer(event) {
    return { x: pointerX(event), y: pointerY(event) };
  }

  function pointerX(event) {
    var docElement = document.documentElement,
     body = document.body || { scrollLeft: 0 };

    return event.pageX || (event.clientX +
      (docElement.scrollLeft || body.scrollLeft) -
      (docElement.clientLeft || 0));
  }

  function pointerY(event) {
    var docElement = document.documentElement,
     body = document.body || { scrollTop: 0 };

    return  event.pageY || (event.clientY +
       (docElement.scrollTop || body.scrollTop) -
       (docElement.clientTop || 0));
  }


  function stop(event) {
    Event.extend(event);
    event.preventDefault();
    event.stopPropagation();

    event.stopped = true;
  }


  Event.Methods = {
    isLeftClick:   isLeftClick,
    isMiddleClick: isMiddleClick,
    isRightClick:  isRightClick,

    element:     element,
    findElement: findElement,

    pointer:  pointer,
    pointerX: pointerX,
    pointerY: pointerY,

    stop: stop
  };

  var methods = Object.keys(Event.Methods).inject({ }, function(m, name) {
    m[name] = Event.Methods[name].methodize();
    return m;
  });

  if (window.attachEvent) {
    function _relatedTarget(event) {
      var element;
      switch (event.type) {
        case 'mouseover':
        case 'mouseenter':
          element = event.fromElement;
          break;
        case 'mouseout':
        case 'mouseleave':
          element = event.toElement;
          break;
        default:
          return null;
      }
      return Element.extend(element);
    }

    var additionalMethods = {
      stopPropagation: function() { this.cancelBubble = true },
      preventDefault:  function() { this.returnValue = false },
      inspect: function() { return '[object Event]' }
    };

    Event.extend = function(event, element) {
      if (!event) return false;

      if (!isIELegacyEvent(event)) return event;

      if (event._extendedByPrototype) return event;
      event._extendedByPrototype = Prototype.emptyFunction;

      var pointer = Event.pointer(event);

      Object.extend(event, {
        target: event.srcElement || element,
        relatedTarget: _relatedTarget(event),
        pageX:  pointer.x,
        pageY:  pointer.y
      });

      Object.extend(event, methods);
      Object.extend(event, additionalMethods);

      return event;
    };
  } else {
    Event.extend = Prototype.K;
  }

  if (window.addEventListener) {
    Event.prototype = window.Event.prototype || document.createEvent('HTMLEvents').__proto__;
    Object.extend(Event.prototype, methods);
  }

  function _createResponder(element, eventName, handler) {
    var registry = Element.retrieve(element, 'prototype_event_registry');

    if (Object.isUndefined(registry)) {
      CACHE.push(element);
      registry = Element.retrieve(element, 'prototype_event_registry', $H());
    }

    var respondersForEvent = registry.get(eventName);
    if (Object.isUndefined(respondersForEvent)) {
      respondersForEvent = [];
      registry.set(eventName, respondersForEvent);
    }

    if (respondersForEvent.pluck('handler').include(handler)) return false;

    var responder;
    if (eventName.include(":")) {
      responder = function(event) {
        if (Object.isUndefined(event.eventName))
          return false;

        if (event.eventName !== eventName)
          return false;

        Event.extend(event, element);
        handler.call(element, event);
      };
    } else {
      if (!MOUSEENTER_MOUSELEAVE_EVENTS_SUPPORTED &&
       (eventName === "mouseenter" || eventName === "mouseleave")) {
        if (eventName === "mouseenter" || eventName === "mouseleave") {
          responder = function(event) {
            Event.extend(event, element);

            var parent = event.relatedTarget;
            while (parent && parent !== element) {
              try { parent = parent.parentNode; }
              catch(e) { parent = element; }
            }

            if (parent === element) return;

            handler.call(element, event);
          };
        }
      } else {
        responder = function(event) {
          Event.extend(event, element);
          handler.call(element, event);
        };
      }
    }

    responder.handler = handler;
    respondersForEvent.push(responder);
    return responder;
  }

  function _destroyCache() {
    for (var i = 0, length = CACHE.length; i < length; i++) {
      Event.stopObserving(CACHE[i]);
      CACHE[i] = null;
    }
  }

  var CACHE = [];

  if (Prototype.Browser.IE)
    window.attachEvent('onunload', _destroyCache);

  if (Prototype.Browser.WebKit)
    window.addEventListener('unload', Prototype.emptyFunction, false);


  var _getDOMEventName = Prototype.K,
      translations = { mouseenter: "mouseover", mouseleave: "mouseout" };

  if (!MOUSEENTER_MOUSELEAVE_EVENTS_SUPPORTED) {
    _getDOMEventName = function(eventName) {
      return (translations[eventName] || eventName);
    };
  }

  function observe(element, eventName, handler) {
    element = $(element);

    var responder = _createResponder(element, eventName, handler);

    if (!responder) return element;

    if (eventName.include(':')) {
      if (element.addEventListener)
        element.addEventListener("dataavailable", responder, false);
      else {
        element.attachEvent("ondataavailable", responder);
        element.attachEvent("onlosecapture", responder);
      }
    } else {
      var actualEventName = _getDOMEventName(eventName);

      if (element.addEventListener)
        element.addEventListener(actualEventName, responder, false);
      else
        element.attachEvent("on" + actualEventName, responder);
    }

    return element;
  }

  function stopObserving(element, eventName, handler) {
    element = $(element);

    var registry = Element.retrieve(element, 'prototype_event_registry');
    if (!registry) return element;

    if (!eventName) {
      registry.each( function(pair) {
        var eventName = pair.key;
        stopObserving(element, eventName);
      });
      return element;
    }

    var responders = registry.get(eventName);
    if (!responders) return element;

    if (!handler) {
      responders.each(function(r) {
        stopObserving(element, eventName, r.handler);
      });
      return element;
    }

    var i = responders.length, responder;
    while (i--) {
      if (responders[i].handler === handler) {
        responder = responders[i];
        break;
      }
    }
    if (!responder) return element;

    if (eventName.include(':')) {
      if (element.removeEventListener)
        element.removeEventListener("dataavailable", responder, false);
      else {
        element.detachEvent("ondataavailable", responder);
        element.detachEvent("onlosecapture", responder);
      }
    } else {
      var actualEventName = _getDOMEventName(eventName);
      if (element.removeEventListener)
        element.removeEventListener(actualEventName, responder, false);
      else
        element.detachEvent('on' + actualEventName, responder);
    }

    registry.set(eventName, responders.without(responder));

    return element;
  }

  function fire(element, eventName, memo, bubble) {
    element = $(element);

    if (Object.isUndefined(bubble))
      bubble = true;

    if (element == document && document.createEvent && !element.dispatchEvent)
      element = document.documentElement;

    var event;
    if (document.createEvent) {
      event = document.createEvent('HTMLEvents');
      event.initEvent('dataavailable', bubble, true);
    } else {
      event = document.createEventObject();
      event.eventType = bubble ? 'ondataavailable' : 'onlosecapture';
    }

    event.eventName = eventName;
    event.memo = memo || { };

    if (document.createEvent)
      element.dispatchEvent(event);
    else
      element.fireEvent(event.eventType, event);

    return Event.extend(event);
  }

  Event.Handler = Class.create({
    initialize: function(element, eventName, selector, callback) {
      this.element   = $(element);
      this.eventName = eventName;
      this.selector  = selector;
      this.callback  = callback;
      this.handler   = this.handleEvent.bind(this);
    },

    start: function() {
      Event.observe(this.element, this.eventName, this.handler);
      return this;
    },

    stop: function() {
      Event.stopObserving(this.element, this.eventName, this.handler);
      return this;
    },

    handleEvent: function(event) {
      var element = Event.findElement(event, this.selector);
      if (element) this.callback.call(this.element, event, element);
    }
  });

  function on(element, eventName, selector, callback) {
    element = $(element);
    if (Object.isFunction(selector) && Object.isUndefined(callback)) {
      callback = selector, selector = null;
    }

    return new Event.Handler(element, eventName, selector, callback).start();
  }

  Object.extend(Event, Event.Methods);

  Object.extend(Event, {
    fire:          fire,
    observe:       observe,
    stopObserving: stopObserving,
    on:            on
  });

  Element.addMethods({
    fire:          fire,

    observe:       observe,

    stopObserving: stopObserving,

    on:            on
  });

  Object.extend(document, {
    fire:          fire.methodize(),

    observe:       observe.methodize(),

    stopObserving: stopObserving.methodize(),

    on:            on.methodize(),

    loaded:        false
  });

  if (window.Event) Object.extend(window.Event, Event);
  else window.Event = Event;
})();

(function() {
  /* Support for the DOMContentLoaded event is based on work by Dan Webb,
     Matthias Miller, Dean Edwards, John Resig, and Diego Perini. */

  var timer;

  function fireContentLoadedEvent() {
    if (document.loaded) return;
    if (timer) window.clearTimeout(timer);
    document.loaded = true;
    document.fire('dom:loaded');
  }

  function checkReadyState() {
    if (document.readyState === 'complete') {
      document.stopObserving('readystatechange', checkReadyState);
      fireContentLoadedEvent();
    }
  }

  function pollDoScroll() {
    try { document.documentElement.doScroll('left'); }
    catch(e) {
      timer = pollDoScroll.defer();
      return;
    }
    fireContentLoadedEvent();
  }

  if (document.addEventListener) {
    document.addEventListener('DOMContentLoaded', fireContentLoadedEvent, false);
  } else {
    document.observe('readystatechange', checkReadyState);
    if (window == top)
      timer = pollDoScroll.defer();
  }

  Event.observe(window, 'load', fireContentLoadedEvent);
})();


Element.addMethods();
/*------------------------------- DEPRECATED -------------------------------*/

Hash.toQueryString = Object.toQueryString;

var Toggle = { display: Element.toggle };

Element.Methods.childOf = Element.Methods.descendantOf;

var Insertion = {
  Before: function(element, content) {
    return Element.insert(element, {before:content});
  },

  Top: function(element, content) {
    return Element.insert(element, {top:content});
  },

  Bottom: function(element, content) {
    return Element.insert(element, {bottom:content});
  },

  After: function(element, content) {
    return Element.insert(element, {after:content});
  }
};

var $continue = new Error('"throw $continue" is deprecated, use "return" instead');

var Position = {
  includeScrollOffsets: false,

  prepare: function() {
    this.deltaX =  window.pageXOffset
                || document.documentElement.scrollLeft
                || document.body.scrollLeft
                || 0;
    this.deltaY =  window.pageYOffset
                || document.documentElement.scrollTop
                || document.body.scrollTop
                || 0;
  },

  within: function(element, x, y) {
    if (this.includeScrollOffsets)
      return this.withinIncludingScrolloffsets(element, x, y);
    this.xcomp = x;
    this.ycomp = y;
    this.offset = Element.cumulativeOffset(element);

    return (y >= this.offset[1] &&
            y <  this.offset[1] + element.offsetHeight &&
            x >= this.offset[0] &&
            x <  this.offset[0] + element.offsetWidth);
  },

  withinIncludingScrolloffsets: function(element, x, y) {
    var offsetcache = Element.cumulativeScrollOffset(element);

    this.xcomp = x + offsetcache[0] - this.deltaX;
    this.ycomp = y + offsetcache[1] - this.deltaY;
    this.offset = Element.cumulativeOffset(element);

    return (this.ycomp >= this.offset[1] &&
            this.ycomp <  this.offset[1] + element.offsetHeight &&
            this.xcomp >= this.offset[0] &&
            this.xcomp <  this.offset[0] + element.offsetWidth);
  },

  overlap: function(mode, element) {
    if (!mode) return 0;
    if (mode == 'vertical')
      return ((this.offset[1] + element.offsetHeight) - this.ycomp) /
        element.offsetHeight;
    if (mode == 'horizontal')
      return ((this.offset[0] + element.offsetWidth) - this.xcomp) /
        element.offsetWidth;
  },


  cumulativeOffset: Element.Methods.cumulativeOffset,

  positionedOffset: Element.Methods.positionedOffset,

  absolutize: function(element) {
    Position.prepare();
    return Element.absolutize(element);
  },

  relativize: function(element) {
    Position.prepare();
    return Element.relativize(element);
  },

  realOffset: Element.Methods.cumulativeScrollOffset,

  offsetParent: Element.Methods.getOffsetParent,

  page: Element.Methods.viewportOffset,

  clone: function(source, target, options) {
    options = options || { };
    return Element.clonePosition(target, source, options);
  }
};

/*--------------------------------------------------------------------------*/

if (!document.getElementsByClassName) document.getElementsByClassName = function(instanceMethods){
  function iter(name) {
    return name.blank() ? null : "[contains(concat(' ', @class, ' '), ' " + name + " ')]";
  }

  instanceMethods.getElementsByClassName = Prototype.BrowserFeatures.XPath ?
  function(element, className) {
    className = className.toString().strip();
    var cond = /\s/.test(className) ? $w(className).map(iter).join('') : iter(className);
    return cond ? document._getElementsByXPath('.//*' + cond, element) : [];
  } : function(element, className) {
    className = className.toString().strip();
    var elements = [], classNames = (/\s/.test(className) ? $w(className) : null);
    if (!classNames && !className) return elements;

    var nodes = $(element).getElementsByTagName('*');
    className = ' ' + className + ' ';

    for (var i = 0, child, cn; child = nodes[i]; i++) {
      if (child.className && (cn = ' ' + child.className + ' ') && (cn.include(className) ||
          (classNames && classNames.all(function(name) {
            return !name.toString().blank() && cn.include(' ' + name + ' ');
          }))))
        elements.push(Element.extend(child));
    }
    return elements;
  };

  return function(className, parentElement) {
    return $(parentElement || document.body).getElementsByClassName(className);
  };
}(Element.Methods);

/*--------------------------------------------------------------------------*/

Element.ClassNames = Class.create();
Element.ClassNames.prototype = {
  initialize: function(element) {
    this.element = $(element);
  },

  _each: function(iterator) {
    this.element.className.split(/\s+/).select(function(name) {
      return name.length > 0;
    })._each(iterator);
  },

  set: function(className) {
    this.element.className = className;
  },

  add: function(classNameToAdd) {
    if (this.include(classNameToAdd)) return;
    this.set($A(this).concat(classNameToAdd).join(' '));
  },

  remove: function(classNameToRemove) {
    if (!this.include(classNameToRemove)) return;
    this.set($A(this).without(classNameToRemove).join(' '));
  },

  toString: function() {
    return $A(this).join(' ');
  }
};

Object.extend(Element.ClassNames.prototype, Enumerable);

/*--------------------------------------------------------------------------*/

(function() {
  window.Selector = Class.create({
    initialize: function(expression) {
      this.expression = expression.strip();
    },

    findElements: function(rootElement) {
      return Prototype.Selector.select(this.expression, rootElement);
    },

    match: function(element) {
      return Prototype.Selector.match(element, this.expression);
    },

    toString: function() {
      return this.expression;
    },

    inspect: function() {
      return "#<Selector: " + this.expression + ">";
    }
  });

  Object.extend(Selector, {
    matchElements: function(elements, expression) {
      var match = Prototype.Selector.match,
          results = [];

      for (var i = 0, length = elements.length; i < length; i++) {
        var element = elements[i];
        if (match(element, expression)) {
          results.push(Element.extend(element));
        }
      }
      return results;
    },

    findElement: function(elements, expression, index) {
      index = index || 0;
      var matchIndex = 0, element;
      for (var i = 0, length = elements.length; i < length; i++) {
        element = elements[i];
        if (Prototype.Selector.match(element, expression) && index === matchIndex++) {
          return Element.extend(element);
        }
      }
    },

    findChildElements: function(element, expressions) {
      var selector = expressions.toArray().join(', ');
      return Prototype.Selector.select(selector, element || document);
    }
  });
})();
/*!
 * jQuery JavaScript Library v1.7.2
 * http://jquery.com/
 *
 * Copyright 2011, John Resig
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * Includes Sizzle.js
 * http://sizzlejs.com/
 * Copyright 2011, The Dojo Foundation
 * Released under the MIT, BSD, and GPL Licenses.
 *
 * Date: Wed Mar 21 12:46:34 2012 -0700
 */var $jq,send,shortcutsON,calc_row,note_option_string,Builder,Effect,Droppables,Draggables,Draggable,SortableObserver,Sortable,Autocompleter,Control,Prototip,Tip,Validator,Validation,$proc,$value,IframeShim,dynamicOptionListCount,dynamicOptionListObjects;(function(d,b){var c=d.document,bx=d.navigator,bv=d.location,a=function(){var a=function(b,c){return new a.fn.init(b,c,n)},H=d.jQuery,G=d.$,n,F=/^(?:[^#<]*(<[\w\W]+>)[^>]*$|#([\w\-]*)$)/,k=/\S/,l=/^\s+/,q=/\s+$/,E=/^<(\w+)\s*\/?>(?:<\/\1>)?$/,D=/^[\],:{}\s]*$/,C=/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g,A=/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,z=/(?:^|:|,)(?:\s*\[)+/g,y=/(webkit)[ \/]([\w.]+)/,t=/(opera)(?:.*version)?[ \/]([\w.]+)/,s=/(msie) ([\w.]+)/,I=/(mozilla)(?:.*? rv:([\w.]+))?/,u=/-([a-z]|[0-9])/ig,v=/^-ms-/,w=function(b,a){return(a+"").toUpperCase()},x=bx.userAgent,h,g,e,B=Object.prototype.toString,j=Object.prototype.hasOwnProperty,i=Array.prototype.push,f=Array.prototype.slice,m=String.prototype.trim,o=Array.prototype.indexOf,p={};a.fn=a.prototype={constructor:a,init:function(d,e,i){var f,h,g,j;if(!d)return this;if(d.nodeType)return this.context=this[0]=d,this.length=1,this;if(d==="body"&&!e&&c.body)return this.context=c,this[0]=c.body,this.selector=d,this.length=1,this;if(typeof d=="string"){if(d.charAt(0)==="<"&&d.charAt(d.length-1)===">"&&d.length>=3?f=[null,d,null]:f=F.exec(d),f&&(f[1]||!e)){if(f[1])return e=e instanceof a?e[0]:e,j=e?e.ownerDocument||e:c,g=E.exec(d),g?a.isPlainObject(e)?(d=[c.createElement(g[1])],a.fn.attr.call(d,e,!0)):d=[j.createElement(g[1])]:(g=a.buildFragment([f[1]],[j]),d=(g.cacheable?a.clone(g.fragment):g.fragment).childNodes),a.merge(this,d);if(h=c.getElementById(f[2]),h&&h.parentNode){if(h.id!==f[2])return i.find(d);this.length=1,this[0]=h}return this.context=c,this.selector=d,this}return!e||e.jquery?(e||i).find(d):this.constructor(e).find(d)}return a.isFunction(d)?i.ready(d):(d.selector!==b&&(this.selector=d.selector,this.context=d.context),a.makeArray(d,this))},selector:"",jquery:"1.7.2",length:0,size:function(){return this.length},toArray:function(){return f.call(this,0)},get:function(a){return a==null?this.toArray():a<0?this[this.length+a]:this[a]},pushStack:function(c,d,e){var b=this.constructor();return a.isArray(c)?i.apply(b,c):a.merge(b,c),b.prevObject=this,b.context=this.context,d==="find"?b.selector=this.selector+(this.selector?" ":"")+e:d&&(b.selector=this.selector+"."+d+"("+e+")"),b},each:function(b,c){return a.each(this,b,c)},ready:function(b){return a.bindReady(),g.add(b),this},eq:function(a){return a=+a,a===-1?this.slice(a):this.slice(a,a+1)},first:function(){return this.eq(0)},last:function(){return this.eq(-1)},slice:function(){return this.pushStack(f.apply(this,arguments),"slice",f.call(arguments).join(","))},map:function(b){return this.pushStack(a.map(this,function(a,c){return b.call(a,c,a)}))},end:function(){return this.prevObject||this.constructor(null)},push:i,sort:[].sort,splice:[].splice},a.fn.init.prototype=a.fn,a.extend=a.fn.extend=function(){var j,g,e,d,h,i,c=arguments[0]||{},f=1,l=arguments.length,k=!1;for(typeof c=="boolean"&&(k=c,c=arguments[1]||{},f=2),typeof c!="object"&&!a.isFunction(c)&&(c={}),l===f&&(c=this,--f);f<l;f++)if((j=arguments[f])!=null)for(g in j){if(e=c[g],d=j[g],c===d)continue;k&&d&&(a.isPlainObject(d)||(h=a.isArray(d)))?(h?(h=!1,i=e&&a.isArray(e)?e:[]):i=e&&a.isPlainObject(e)?e:{},c[g]=a.extend(k,i,d)):d!==b&&(c[g]=d)}return c},a.extend({noConflict:function(b){return d.$===a&&(d.$=G),b&&d.jQuery===a&&(d.jQuery=H),a},isReady:!1,readyWait:1,holdReady:function(b){b?a.readyWait++:a.ready(!0)},ready:function(b){if(b===!0&&!--a.readyWait||b!==!0&&!a.isReady){if(!c.body)return setTimeout(a.ready,1);if(a.isReady=!0,b!==!0&&--a.readyWait>0)return;g.fireWith(c,[a]),a.fn.trigger&&a(c).trigger("ready").off("ready")}},bindReady:function(){if(g)return;if(g=a.Callbacks("once memory"),c.readyState==="complete")return setTimeout(a.ready,1);if(c.addEventListener)c.addEventListener("DOMContentLoaded",e,!1),d.addEventListener("load",a.ready,!1);else if(c.attachEvent){c.attachEvent("onreadystatechange",e),d.attachEvent("onload",a.ready);var b=!1;try{b=d.frameElement==null}catch(a){}c.documentElement.doScroll&&b&&r()}},isFunction:function(b){return a.type(b)==="function"},isArray:Array.isArray||function(b){return a.type(b)==="array"},isWindow:function(a){return a!=null&&a==a.window},isNumeric:function(a){return!isNaN(parseFloat(a))&&isFinite(a)},type:function(a){return a==null?String(a):p[B.call(a)]||"object"},isPlainObject:function(c){if(!c||a.type(c)!=="object"||c.nodeType||a.isWindow(c))return!1;try{if(c.constructor&&!j.call(c,"constructor")&&!j.call(c.constructor.prototype,"isPrototypeOf"))return!1}catch(a){return!1}var d;for(d in c);return d===b||j.call(c,d)},isEmptyObject:function(a){for(var b in a)return!1;return!0},error:function(a){throw new Error(a)},parseJSON:function(b){if(typeof b!="string"||!b)return null;if(b=a.trim(b),d.JSON&&d.JSON.parse)return d.JSON.parse(b);if(D.test(b.replace(C,"@").replace(A,"]").replace(z,"")))return new Function("return "+b)();a.error("Invalid JSON: "+b)},parseXML:function(e){if(typeof e!="string"||!e)return null;var c,f;try{d.DOMParser?(f=new DOMParser,c=f.parseFromString(e,"text/xml")):(c=new ActiveXObject("Microsoft.XMLDOM"),c.async="false",c.loadXML(e))}catch(a){c=b}return(!c||!c.documentElement||c.getElementsByTagName("parsererror").length)&&a.error("Invalid XML: "+e),c},noop:function(){},globalEval:function(a){a&&k.test(a)&&(d.execScript||function(a){d.eval.call(d,a)})(a)},camelCase:function(a){return a.replace(v,"ms-").replace(u,w)},nodeName:function(a,b){return a.nodeName&&a.nodeName.toUpperCase()===b.toUpperCase()},each:function(c,f,g){var d,e=0,h=c.length,i=h===b||a.isFunction(c);if(g){if(i){for(d in c)if(f.apply(c[d],g)===!1)break}else for(;e<h;)if(f.apply(c[e++],g)===!1)break}else if(i){for(d in c)if(f.call(c[d],d,c[d])===!1)break}else for(;e<h;)if(f.call(c[e],e,c[e++])===!1)break;return c},trim:m?function(a){return a==null?"":m.call(a)}:function(a){return a==null?"":a.toString().replace(l,"").replace(q,"")},makeArray:function(b,e){var c=e||[],d;return b!=null&&(d=a.type(b),b.length==null||d==="string"||d==="function"||d==="regexp"||a.isWindow(b)?i.call(c,b):a.merge(c,b)),c},inArray:function(d,b,a){var c;if(b){if(o)return o.call(b,d,a);for(c=b.length,a=a?a<0?Math.max(0,c+a):a:0;a<c;a++)if(a in b&&b[a]===d)return a}return-1},merge:function(a,c){var e=a.length,d=0,f;if(typeof c.length=="number")for(f=c.length;d<f;d++)a[e++]=c[d];else while(c[d]!==b)a[e++]=c[d++];return a.length=e,a},grep:function(b,f,c){var d=[],e,a,g;c=!!c;for(a=0,g=b.length;a<g;a++)e=!!f(b[a],a),c!==e&&d.push(b[a]);return d},map:function(c,j,i){var e,h,f=[],g=0,d=c.length,k=c instanceof a||d!==b&&typeof d=="number"&&(d>0&&c[0]&&c[d-1]||d===0||a.isArray(c));if(k){for(;g<d;g++)e=j(c[g],g,i),e!=null&&(f[f.length]=e)}else for(h in c)e=j(c[h],h,i),e!=null&&(f[f.length]=e);return f.concat.apply([],f)},guid:1,proxy:function(c,d){var g,h,e;return(typeof d=="string"&&(g=c[d],d=c,c=g),!a.isFunction(c))?b:(h=f.call(arguments,2),e=function(){return c.apply(d,h.concat(f.call(arguments)))},e.guid=c.guid=c.guid||e.guid||a.guid++,e)},access:function(d,c,f,g,i,j,k){var h,l=f==null,e=0,m=d.length;if(f&&typeof f=="object"){for(e in f)a.access(d,c,e,f[e],1,j,g);i=1}else if(g!==b){if(h=k===b&&a.isFunction(g),l&&(h?(h=c,c=function(b,d,c){return h.call(a(b),c)}):(c.call(d,g),c=null)),c)for(;e<m;e++)c(d[e],f,h?g.call(d[e],e,c(d[e],f)):g,k);i=1}return i?d:l?c.call(d):m?c(d[0],f):j},now:function(){return(new Date).getTime()},uaMatch:function(a){a=a.toLowerCase();var b=y.exec(a)||t.exec(a)||s.exec(a)||a.indexOf("compatible")<0&&I.exec(a)||[];return{browser:b[1]||"",version:b[2]||"0"}},sub:function(){function b(a,c){return new b.fn.init(a,c)}a.extend(!0,b,this),b.superclass=this,b.fn=b.prototype=this(),b.fn.constructor=b,b.sub=this.sub,b.fn.init=function(e,c){return c&&c instanceof a&&!(c instanceof b)&&(c=b(c)),a.fn.init.call(this,e,c,d)},b.fn.init.prototype=b.fn;var d=b(c);return b},browser:{}}),a.each("Boolean Number String Function Array Date RegExp Object".split(" "),function(b,a){p["[object "+a+"]"]=a.toLowerCase()}),h=a.uaMatch(x),h.browser&&(a.browser[h.browser]=!0,a.browser.version=h.version),a.browser.webkit&&(a.browser.safari=!0),k.test("\xA0")&&(l=/^[\s\xA0]+/,q=/[\s\xA0]+$/),n=a(c),c.addEventListener?e=function(){c.removeEventListener("DOMContentLoaded",e,!1),a.ready()}:c.attachEvent&&(e=function(){c.readyState==="complete"&&(c.detachEvent("onreadystatechange",e),a.ready())});function r(){if(a.isReady)return;try{c.documentElement.doScroll("left")}catch(a){setTimeout(r,1);return}a.ready()}return a}(),ap={},x,bt,br,aa,v,by,bq,bp,bo,$,_,g,X,P,A,N,bl,bk,bj,M,bi,bh,bg,J,be,bd,a$,a_,aZ,Q,aY,V,aX,y,Y,Z,aW,aV,aT,aS,ac,ad,ae,aR,e,z,H,aF,aC,aB,E,aA,az,ay,h,n,av,at,aD,aE,aq,aG,aH,aI,aJ,aK,aL,ao,aN,aO,ai,aQ,af,ab,G,ax,j,i,W,bc,r,F,bf,o,I,f,k,bm,bn,t,s,w,C,bw,au;function bu(a){var c=ap[a]={},b,d;a=a.split(/\s+/);for(b=0,d=a.length;b<d;b++)c[a[b]]=!0;return c}a.Callbacks=function(e){e=e?ap[e]||bu(e):{};var c=[],f=[],d,l,j,k,i,h,m=function(h){var d,i,b,f,j;for(d=0,i=h.length;d<i;d++)b=h[d],f=a.type(b),f==="array"?m(b):f==="function"&&(!e.unique||!g.has(b))&&c.push(b)},n=function(b,a){for(a=a||[],d=!e.memory||[b,a],l=!0,j=!0,h=k||0,k=0,i=c.length;c&&h<i;h++)if(c[h].apply(b,a)===!1&&e.stopOnFalse){d=!0;break}j=!1,c&&(e.once?d===!0?g.disable():c=[]:f&&f.length&&(d=f.shift(),g.fireWith(d[0],d[1])))},g={add:function(){if(c){var a=c.length;m(arguments),j?i=c.length:d&&d!==!0&&(k=a,n(d[0],d[1]))}return this},remove:function(){var d,b,f,a;if(c)for(d=arguments,b=0,f=d.length;b<f;b++)for(a=0;a<c.length;a++)if(d[b]===c[a]){if(j&&a<=i&&(i--,a<=h&&h--),c.splice(a--,1),e.unique)break}return this},has:function(b){if(c)for(var a=0,d=c.length;a<d;a++)if(b===c[a])return!0;return!1},empty:function(){return c=[],this},disable:function(){return c=f=d=b,this},disabled:function(){return!c},lock:function(){return f=b,(!d||d===!0)&&g.disable(),this},locked:function(){return!f},fireWith:function(a,b){return f&&(j?e.once||f.push([a,b]):e.once&&d||n(a,b)),this},fire:function(){return g.fireWith(this,arguments),this},fired:function(){return!!l}};return g},x=[].slice,a.extend({Deferred:function(j){var f=a.Callbacks("once memory"),g=a.Callbacks("once memory"),e=a.Callbacks("memory"),h="pending",i={resolve:f,reject:g,notify:e},d={done:f.add,fail:g.add,progress:e.add,state:function(){return h},isResolved:f.fired,isRejected:g.fired,then:function(a,c,d){return b.done(a).fail(c).progress(d),this},always:function(){return b.done.apply(b,arguments).fail.apply(b,arguments),this},pipe:function(c,d,e){return a.Deferred(function(f){a.each({done:[c,"resolve"],fail:[d,"reject"],progress:[e,"notify"]},function(d,e){var g=e[0],h=e[1],c;a.isFunction(g)?b[d](function(){c=g.apply(this,arguments),c&&a.isFunction(c.promise)?c.promise().then(f.resolve,f.reject,f.notify):f[h+"With"](this===b?f:this,[c])}):b[d](f[h])})}).promise()},promise:function(a){if(a==null)a=d;else for(var b in d)a[b]=d[b];return a}},b=d.promise({}),c;for(c in i)b[c]=i[c].fire,b[c+"With"]=i[c].fireWith;return b.done(function(){h="resolved"},g.disable,e.lock).fail(function(){h="rejected"},f.disable,e.lock),j&&j.call(b,b),b},when:function(f){var d=x.call(arguments,0),c=0,e=d.length,h=new Array(e),g=e,l=e,b=e<=1&&f&&a.isFunction(f.promise)?f:a.Deferred(),i=b.promise();function j(a){return function(c){d[a]=arguments.length>1?x.call(arguments,0):c,--g||b.resolveWith(b,d)}}function k(a){return function(c){h[a]=arguments.length>1?x.call(arguments,0):c,b.notifyWith(i,h)}}if(e>1){for(;c<e;c++)d[c]&&d[c].promise&&a.isFunction(d[c].promise)?d[c].promise().then(j(c),b.reject,k(c)):--g;g||b.resolveWith(b,d)}else b!==f&&b.resolveWith(b,e?[f]:[]);return i}}),a.support=function(){var e,o,h,m,k,f,g,j,p,l,n,i,b=c.createElement("div"),q=c.documentElement;if(b.setAttribute("className","t"),b.innerHTML="   <link/><table></table><a href='/a' style='top:1px;float:left;opacity:.55;'>a</a><input type='checkbox'/>",o=b.getElementsByTagName("*"),h=b.getElementsByTagName("a")[0],!o||!o.length||!h)return{};m=c.createElement("select"),k=m.appendChild(c.createElement("option")),f=b.getElementsByTagName("input")[0],e={leadingWhitespace:b.firstChild.nodeType===3,tbody:!b.getElementsByTagName("tbody").length,htmlSerialize:!!b.getElementsByTagName("link").length,style:/top/.test(h.getAttribute("style")),hrefNormalized:h.getAttribute("href")==="/a",opacity:/^0.55/.test(h.style.opacity),cssFloat:!!h.style.cssFloat,checkOn:f.value==="on",optSelected:k.selected,getSetAttribute:b.className!=="t",enctype:!!c.createElement("form").enctype,html5Clone:c.createElement("nav").cloneNode(!0).outerHTML!=="<:nav></:nav>",submitBubbles:!0,changeBubbles:!0,focusinBubbles:!1,deleteExpando:!0,noCloneEvent:!0,inlineBlockNeedsLayout:!1,shrinkWrapBlocks:!1,reliableMarginRight:!0,pixelMargin:!0},a.boxModel=e.boxModel=c.compatMode==="CSS1Compat",f.checked=!0,e.noCloneChecked=f.cloneNode(!0).checked,m.disabled=!0,e.optDisabled=!k.disabled;try{delete b.test}catch(a){e.deleteExpando=!1}if(!b.addEventListener&&b.attachEvent&&b.fireEvent&&(b.attachEvent("onclick",function(){e.noCloneEvent=!1}),b.cloneNode(!0).fireEvent("onclick")),f=c.createElement("input"),f.value="t",f.setAttribute("type","radio"),e.radioValue=f.value==="t",f.setAttribute("checked","checked"),f.setAttribute("name","t"),b.appendChild(f),g=c.createDocumentFragment(),g.appendChild(b.lastChild),e.checkClone=g.cloneNode(!0).cloneNode(!0).lastChild.checked,e.appendChecked=f.checked,g.removeChild(f),g.appendChild(b),b.attachEvent)for(n in{submit:1,change:1,focusin:1})l="on"+n,i=l in b,i||(b.setAttribute(l,"return;"),i=typeof b[l]=="function"),e[n+"Bubbles"]=i;return g.removeChild(b),g=m=k=b=f=null,a(function(){var g,k,f,u,t,m,h,o,p,s,q,r,l,n=c.getElementsByTagName("body")[0];if(!n)return;o=1,l="padding:0;margin:0;border:",q="position:absolute;top:0;left:0;width:1px;height:1px;",r=l+"0;visibility:hidden;",p="style='"+q+l+"5px solid #000;",s="<div "+p+"display:block;'><div style='"+l+"0;display:block;overflow:hidden;'></div></div>"+"<table "+p+"' cellpadding='0' cellspacing='0'>"+"<tr><td></td></tr></table>",g=c.createElement("div"),g.style.cssText=r+"width:0;height:0;position:static;top:0;margin-top:"+o+"px",n.insertBefore(g,n.firstChild),b=c.createElement("div"),g.appendChild(b),b.innerHTML="<table><tr><td style='"+l+"0;display:none'></td><td>t</td></tr></table>",j=b.getElementsByTagName("td"),i=j[0].offsetHeight===0,j[0].style.display="",j[1].style.display="none",e.reliableHiddenOffsets=i&&j[0].offsetHeight===0,d.getComputedStyle&&(b.innerHTML="",h=c.createElement("div"),h.style.width="0",h.style.marginRight="0",b.style.width="2px",b.appendChild(h),e.reliableMarginRight=(parseInt((d.getComputedStyle(h,null)||{marginRight:0}).marginRight,10)||0)===0),typeof b.style.zoom!="undefined"&&(b.innerHTML="",b.style.width=b.style.padding="1px",b.style.border=0,b.style.overflow="hidden",b.style.display="inline",b.style.zoom=1,e.inlineBlockNeedsLayout=b.offsetWidth===3,b.style.display="block",b.style.overflow="visible",b.innerHTML="<div style='width:5px;'></div>",e.shrinkWrapBlocks=b.offsetWidth!==3),b.style.cssText=q+r,b.innerHTML=s,k=b.firstChild,f=k.firstChild,t=k.nextSibling.firstChild.firstChild,m={doesNotAddBorder:f.offsetTop!==5,doesAddBorderForTableAndCells:t.offsetTop===5},f.style.position="fixed",f.style.top="20px",m.fixedPosition=f.offsetTop===20||f.offsetTop===15,f.style.position=f.style.top="",k.style.overflow="hidden",k.style.position="relative",m.subtractsBorderForOverflowNotVisible=f.offsetTop===-5,m.doesNotIncludeMarginInBodyOffset=n.offsetTop!==o,d.getComputedStyle&&(b.style.marginTop="1%",e.pixelMargin=(d.getComputedStyle(b,null)||{marginTop:0}).marginTop!=="1%"),typeof g.style.zoom!="undefined"&&(g.style.zoom=1),n.removeChild(g),h=b=g=null,a.extend(e,m)}),e}(),bt=/^(?:\{.*\}|\[.*\])$/,br=/([A-Z])/g,a.extend({cache:{},uuid:0,expando:"jQuery"+(a.fn.jquery+Math.random()).replace(/\D/g,""),noData:{embed:!0,object:"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000",applet:!0},hasData:function(b){return b=b.nodeType?a.cache[b[a.expando]]:b[a.expando],!!b&&!D(b)},data:function(g,d,l,k){if(!a.acceptData(g))return;var m,f,i,h=a.expando,n=typeof d=="string",j=g.nodeType,e=j?a.cache:g,c=j?g[h]:g[h]&&h,o=d==="events";if((!c||!e[c]||!o&&!k&&!e[c].data)&&n&&l===b)return;return(c||(j?g[h]=c=++a.uuid:c=h),e[c]||(e[c]={},j||(e[c].toJSON=a.noop)),(typeof d=="object"||typeof d=="function")&&(k?e[c]=a.extend(e[c],d):e[c].data=a.extend(e[c].data,d)),m=f=e[c],k||(f.data||(f.data={}),f=f.data),l!==b&&(f[a.camelCase(d)]=l),o&&!f[d])?m.events:(n?(i=f[d],i==null&&(i=f[a.camelCase(d)])):i=f,i)},removeData:function(c,b,j){if(!a.acceptData(c))return;var f,h,k,g=a.expando,i=c.nodeType,d=i?a.cache:c,e=i?c[g]:g;if(!d[e])return;if(b){if(f=j?d[e]:d[e].data,f){a.isArray(b)||(b in f?b=[b]:(b=a.camelCase(b),b in f?b=[b]:b=b.split(" ")));for(h=0,k=b.length;h<k;h++)delete f[b[h]];if(!(j?D:a.isEmptyObject)(f))return}}if(!j){if(delete d[e].data,!D(d[e]))return}a.support.deleteExpando||!d.setInterval?delete d[e]:d[e]=null,i&&(a.support.deleteExpando?delete c[g]:c.removeAttribute?c.removeAttribute(g):c[g]=null)},_data:function(b,c,d){return a.data(b,c,d,!0)},acceptData:function(b){if(b.nodeName){var c=a.noData[b.nodeName.toLowerCase()];if(c)return!(c===!0||b.getAttribute("classid")!==c)}return!0}}),a.fn.extend({data:function(f,l){var c,h,i,g,k,e=this[0],j=0,d=null;if(f===b){if(this.length){if(d=a.data(e),e.nodeType===1&&!a._data(e,"parsedAttrs")){i=e.attributes;for(k=i.length;j<k;j++)g=i[j].name,g.indexOf("data-")===0&&(g=a.camelCase(g.substring(5)),ah(e,g,d[g]));a._data(e,"parsedAttrs",!0)}}return d}return typeof f=="object"?this.each(function(){a.data(this,f)}):(c=f.split(".",2),c[1]=c[1]?"."+c[1]:"",h=c[1]+"!",a.access(this,function(g){if(g===b)return d=this.triggerHandler("getData"+h,[c[0]]),d===b&&e&&(d=a.data(e,f),d=ah(e,f,d)),d===b&&c[1]?this.data(c[0]):d;c[1]=g,this.each(function(){var b=a(this);b.triggerHandler("setData"+h,c),a.data(this,f,g),b.triggerHandler("changeData"+h,c)})},null,l,arguments.length>1,null,!1))},removeData:function(b){return this.each(function(){a.removeData(this,b)})}});function ah(d,e,c){if(c===b&&d.nodeType===1){var f="data-"+e.replace(br,"-$1").toLowerCase();if(c=d.getAttribute(f),typeof c=="string"){try{c=c==="true"||(c!=="false"&&(c==="null"?null:a.isNumeric(c)?+c:bt.test(c)?a.parseJSON(c):c))}catch(a){}a.data(d,e,c)}else c=b}return c}function D(c){for(var b in c){if(b==="data"&&a.isEmptyObject(c[b]))continue;if(b!=="toJSON")return!1}return!0}function ag(b,c,d){var e=c+"defer",f=c+"queue",g=c+"mark",h=a._data(b,e);h&&(d==="queue"||!a._data(b,f))&&(d==="mark"||!a._data(b,g))&&setTimeout(function(){!a._data(b,f)&&!a._data(b,g)&&(a.removeData(b,e,!0),h.fire())},0)}a.extend({_mark:function(c,b){c&&(b=(b||"fx")+"mark",a._data(c,b,(a._data(c,b)||0)+1))},_unmark:function(d,b,c){if(d!==!0&&(c=b,b=d,d=!1),b){c=c||"fx";var e=c+"mark",f=d?0:(a._data(b,e)||1)-1;f?a._data(b,e,f):(a.removeData(b,e,!0),ag(b,c,"mark"))}},queue:function(e,c,d){var b;if(e)return c=(c||"fx")+"queue",b=a._data(e,c),d&&(!b||a.isArray(d)?b=a._data(e,c,a.makeArray(d)):b.push(d)),b||[]},dequeue:function(c,b){b=b||"fx";var d=a.queue(c,b),e=d.shift(),f={};e==="inprogress"&&(e=d.shift()),e&&(b==="fx"&&d.unshift("inprogress"),a._data(c,b+".run",f),e.call(c,function(){a.dequeue(c,b)},f)),d.length||(a.removeData(c,b+"queue "+b+".run",!0),ag(c,b,"queue"))}}),a.fn.extend({queue:function(c,d){var e=2;return(typeof c!="string"&&(d=c,c="fx",e--),arguments.length<e)?a.queue(this[0],c):d===b?this:this.each(function(){var b=a.queue(this,c,d);c==="fx"&&b[0]!=="inprogress"&&a.dequeue(this,c)})},dequeue:function(b){return this.each(function(){a.dequeue(this,b)})},delay:function(b,c){return b=a.fx?a.fx.speeds[b]||b:b,c=c||"fx",this.queue(c,function(a,c){var d=setTimeout(a,b);c.stop=function(){clearTimeout(d)}})},clearQueue:function(a){return this.queue(a||"fx",[])},promise:function(c,f){typeof c!="string"&&(f=c,c=b),c=c||"fx";var g=a.Deferred(),d=this,e=d.length,h=1,i=c+"defer",l=c+"queue",m=c+"mark",j;function k(){--h||g.resolveWith(d,[d])}while(e--)(j=a.data(d[e],i,b,!0)||(a.data(d[e],l,b,!0)||a.data(d[e],m,b,!0))&&a.data(d[e],i,a.Callbacks("once memory"),!0))&&(h++,j.add(k));return k(),g.promise(f)}}),aa=/[\n\t\r]/g,v=/\s+/,by=/\r/g,bq=/^(?:button|input)$/i,bp=/^(?:button|input|object|select|textarea)$/i,bo=/^a(?:rea)?$/i,$=/^(?:autofocus|autoplay|async|checked|controls|defer|disabled|hidden|loop|multiple|open|readonly|required|scoped|selected)$/i,_=a.support.getSetAttribute,a.fn.extend({attr:function(b,c){return a.access(this,a.attr,b,c,arguments.length>1)},removeAttr:function(b){return this.each(function(){a.removeAttr(this,b)})},prop:function(b,c){return a.access(this,a.prop,b,c,arguments.length>1)},removeProp:function(c){return c=a.propFix[c]||c,this.each(function(){try{this[c]=b,delete this[c]}catch(a){}})},addClass:function(b){var d,f,h,c,g,e,i;if(a.isFunction(b))return this.each(function(c){a(this).addClass(b.call(this,c,this.className))});if(b&&typeof b=="string"){d=b.split(v);for(f=0,h=this.length;f<h;f++)if(c=this[f],c.nodeType===1)if(!c.className&&d.length===1)c.className=b;else{g=" "+c.className+" ";for(e=0,i=d.length;e<i;e++)~g.indexOf(" "+d[e]+" ")||(g+=d[e]+" ");c.className=a.trim(g)}}return this},removeClass:function(c){var h,e,i,d,f,g,j;if(a.isFunction(c))return this.each(function(b){a(this).removeClass(c.call(this,b,this.className))});if(c&&typeof c=="string"||c===b){h=(c||"").split(v);for(e=0,i=this.length;e<i;e++)if(d=this[e],d.nodeType===1&&d.className)if(c){f=(" "+d.className+" ").replace(aa," ");for(g=0,j=h.length;g<j;g++)f=f.replace(" "+h[g]+" "," ");d.className=a.trim(f)}else d.className=""}return this},toggleClass:function(b,c){var d=typeof b,e=typeof c=="boolean";return a.isFunction(b)?this.each(function(d){a(this).toggleClass(b.call(this,d,this.className,c),c)}):this.each(function(){if(d==="string")for(var f,i=0,h=a(this),g=c,j=b.split(v);f=j[i++];)g=e?g:!h.hasClass(f),h[g?"addClass":"removeClass"](f);else(d==="undefined"||d==="boolean")&&(this.className&&a._data(this,"__className__",this.className),this.className=this.className||b===!1?"":a._data(this,"__className__")||"")})},hasClass:function(b){for(var c=" "+b+" ",a=0,d=this.length;a<d;a++)if(this[a].nodeType===1&&(" "+this[a].className+" ").replace(aa," ").indexOf(c)>-1)return!0;return!1},val:function(f){var c,d,g,e=this[0];if(!arguments.length){if(e)return(c=a.valHooks[e.type]||a.valHooks[e.nodeName.toLowerCase()],c&&"get"in c&&(d=c.get(e,"value"))!==b)?d:(d=e.value,typeof d=="string"?d.replace(by,""):d==null?"":d);return}return g=a.isFunction(f),this.each(function(e){var h=a(this),d;if(this.nodeType!==1)return;g?d=f.call(this,e,h.val()):d=f,d==null?d="":typeof d=="number"?d+="":a.isArray(d)&&(d=a.map(d,function(a){return a==null?"":a+""})),c=a.valHooks[this.type]||a.valHooks[this.nodeName.toLowerCase()],(!c||!("set"in c)||c.set(this,d,"value")===b)&&(this.value=d)})}}),a.extend({valHooks:{option:{get:function(a){var b=a.attributes.value;return!b||b.specified?a.value:a.text}},select:{get:function(g){var i,d,j,b,e=g.selectedIndex,h=[],c=g.options,f=g.type==="select-one";if(e<0)return null;for(d=f?e:0,j=f?e+1:c.length;d<j;d++)if(b=c[d],b.selected&&(a.support.optDisabled?!b.disabled:b.getAttribute("disabled")===null)&&(!b.parentNode.disabled||!a.nodeName(b.parentNode,"optgroup"))){if(i=a(b).val(),f)return i;h.push(i)}return f&&!h.length&&c.length?a(c[e]).val():h},set:function(c,d){var b=a.makeArray(d);return a(c).find("option").each(function(){this.selected=a.inArray(a(this).val(),b)>=0}),b.length||(c.selectedIndex=-1),b}}},attrFn:{val:!0,css:!0,html:!0,text:!0,data:!0,width:!0,height:!0,offset:!0},attr:function(d,c,e,k){var f,h,i,j=d.nodeType;if(!d||j===3||j===8||j===2)return;if(k&&c in a.attrFn)return a(d)[c](e);if(typeof d.getAttribute=="undefined")return a.prop(d,c,e);if(i=j!==1||!a.isXMLDoc(d),i&&(c=c.toLowerCase(),h=a.attrHooks[c]||($.test(c)?X:g)),e!==b){if(e===null){a.removeAttr(d,c);return}return h&&"set"in h&&i&&(f=h.set(d,e,c))!==b?f:(d.setAttribute(c,""+e),e)}return h&&"get"in h&&i&&(f=h.get(d,c))!==null?f:(f=d.getAttribute(c),f===null?b:f)},removeAttr:function(c,i){var d,f,b,h,g,e=0;if(i&&c.nodeType===1)for(f=i.toLowerCase().split(v),h=f.length;e<h;e++)b=f[e],b&&(d=a.propFix[b]||b,g=$.test(b),g||a.attr(c,b,""),c.removeAttribute(_?b:d),g&&d in c&&(c[d]=!1))},attrHooks:{type:{set:function(b,c){if(bq.test(b.nodeName)&&b.parentNode)a.error("type property can't be changed");else if(!a.support.radioValue&&c==="radio"&&a.nodeName(b,"input")){var d=b.value;return b.setAttribute("type",c),d&&(b.value=d),c}}},value:{get:function(b,c){return g&&a.nodeName(b,"button")?g.get(b,c):c in b?b.value:null},set:function(b,c,d){if(g&&a.nodeName(b,"button"))return g.set(b,c,d);b.value=c}}},propFix:{tabindex:"tabIndex",readonly:"readOnly",for:"htmlFor",class:"className",maxlength:"maxLength",cellspacing:"cellSpacing",cellpadding:"cellPadding",rowspan:"rowSpan",colspan:"colSpan",usemap:"useMap",frameborder:"frameBorder",contenteditable:"contentEditable"},prop:function(d,c,h){var f,e,i,g=d.nodeType;if(!d||g===3||g===8||g===2)return;return i=g!==1||!a.isXMLDoc(d),i&&(c=a.propFix[c]||c,e=a.propHooks[c]),h!==b?e&&"set"in e&&(f=e.set(d,h,c))!==b?f:d[c]=h:e&&"get"in e&&(f=e.get(d,c))!==null?f:d[c]},propHooks:{tabIndex:{get:function(a){var c=a.getAttributeNode("tabindex");return c&&c.specified?parseInt(c.value,10):bp.test(a.nodeName)||bo.test(a.nodeName)&&a.href?0:b}}}}),a.attrHooks.tabindex=a.propHooks.tabIndex,X={get:function(d,c){var e,f=a.prop(d,c);return f===!0||typeof f!="boolean"&&(e=d.getAttributeNode(c))&&e.nodeValue!==!1?c.toLowerCase():b},set:function(c,e,b){var d;return e===!1?a.removeAttr(c,b):(d=a.propFix[b]||b,d in c&&(c[d]=!0),c.setAttribute(b,b.toLowerCase())),b}},_||(P={name:!0,id:!0,coords:!0},g=a.valHooks.button={get:function(d,c){var a;return a=d.getAttributeNode(c),a&&(P[c]?a.nodeValue!=="":a.specified)?a.nodeValue:b},set:function(b,e,d){var a=b.getAttributeNode(d);return a||(a=c.createAttribute(d),b.setAttributeNode(a)),a.nodeValue=e+""}},a.attrHooks.tabindex.set=g.set,a.each(["width","height"],function(c,b){a.attrHooks[b]=a.extend(a.attrHooks[b],{set:function(c,a){if(a==="")return c.setAttribute(b,"auto"),a}})}),a.attrHooks.contenteditable={get:g.get,set:function(b,a,c){a===""&&(a="false"),g.set(b,a,c)}}),a.support.hrefNormalized||a.each(["href","src","width","height"],function(d,c){a.attrHooks[c]=a.extend(a.attrHooks[c],{get:function(d){var a=d.getAttribute(c,2);return a===null?b:a}})}),a.support.style||(a.attrHooks.style={get:function(a){return a.style.cssText.toLowerCase()||b},set:function(a,b){return a.style.cssText=""+b}}),a.support.optSelected||(a.propHooks.selected=a.extend(a.propHooks.selected,{get:function(b){var a=b.parentNode;return a&&(a.selectedIndex,a.parentNode&&a.parentNode.selectedIndex),null}})),a.support.enctype||(a.propFix.enctype="encoding"),a.support.checkOn||a.each(["radio","checkbox"],function(){a.valHooks[this]={get:function(a){return a.getAttribute("value")===null?"on":a.value}}}),a.each(["radio","checkbox"],function(){a.valHooks[this]=a.extend(a.valHooks[this],{set:function(b,c){if(a.isArray(c))return b.checked=a.inArray(a(b).val(),c)>=0}})}),A=/^(?:textarea|input|select)$/i,N=/^([^\.]*)?(?:\.(.+))?$/,bl=/(?:^|\s)hover(\.\S+)?\b/,bk=/^key/,bj=/^(?:mouse|contextmenu)|click/,M=/^(?:focusinfocus|focusoutblur)$/,bi=/^(\w*)(?:#([\w\-]+))?(?:\.([\w\-]+))?$/,bh=function(b){var a=bi.exec(b);return a&&(a[1]=(a[1]||"").toLowerCase(),a[3]=a[3]&&new RegExp("(?:^|\\s)"+a[3]+"(?:\\s|$)")),a},bg=function(b,a){var c=b.attributes||{};return(!a[1]||b.nodeName.toLowerCase()===a[1])&&(!a[2]||(c.id||{}).value===a[2])&&(!a[3]||a[3].test((c.class||{}).value))},J=function(b){return a.event.special.hover?b:b.replace(bl,"mouseenter$1 mouseleave$1")},a.event={add:function(d,m,e,r,i){var k,f,l,p,o,c,q,j,n,s,h,g;if(d.nodeType===3||d.nodeType===8||!m||!e||!(k=a._data(d)))return;e.handler&&(n=e,e=n.handler,i=n.selector),e.guid||(e.guid=a.guid++),l=k.events,l||(k.events=l={}),f=k.handle,f||(k.handle=f=function(c){return typeof a!="undefined"&&(!c||a.event.triggered!==c.type)?a.event.dispatch.apply(f.elem,arguments):b},f.elem=d),m=a.trim(J(m)).split(" ");for(p=0;p<m.length;p++)o=N.exec(m[p])||[],c=o[1],q=(o[2]||"").split(".").sort(),g=a.event.special[c]||{},c=(i?g.delegateType:g.bindType)||c,g=a.event.special[c]||{},j=a.extend({type:c,origType:o[1],data:r,handler:e,guid:e.guid,selector:i,quick:i&&bh(i),namespace:q.join(".")},n),h=l[c],h||(h=l[c]=[],h.delegateCount=0,(!g.setup||g.setup.call(d,r,q,f)===!1)&&(d.addEventListener?d.addEventListener(c,f,!1):d.attachEvent&&d.attachEvent("on"+c,f))),g.add&&(g.add.call(d,j),j.handler.guid||(j.handler.guid=e.guid)),i?h.splice(h.delegateCount++,0,j):h.push(j),a.event.global[c]=!0;d=null},global:{},remove:function(g,l,p,j,s){var m=a.hasData(g)&&a._data(g),h,o,b,r,f,q,k,i,e,n,c,d;if(!m||!(i=m.events))return;l=a.trim(J(l||"")).split(" ");for(h=0;h<l.length;h++){if(o=N.exec(l[h])||[],b=r=o[1],f=o[2],!b){for(b in i)a.event.remove(g,b+l[h],p,j,!0);continue}e=a.event.special[b]||{},b=(j?e.delegateType:e.bindType)||b,c=i[b]||[],q=c.length,f=f?new RegExp("(^|\\.)"+f.split(".").sort().join("\\.(?:.*\\.)?")+"(\\.|$)"):null;for(k=0;k<c.length;k++)d=c[k],(s||r===d.origType)&&(!p||p.guid===d.guid)&&(!f||f.test(d.namespace))&&(!j||j===d.selector||j==="**"&&d.selector)&&(c.splice(k--,1),d.selector&&c.delegateCount--,e.remove&&e.remove.call(g,d));c.length===0&&q!==c.length&&((!e.teardown||e.teardown.call(g,f)===!1)&&a.removeEvent(g,b,m.handle),delete i[b])}a.isEmptyObject(i)&&(n=m.handle,n&&(n.elem=null),a.removeData(g,["events","handle"],!0))},customEvent:{getData:!0,setData:!0,changeData:!0},trigger:function(c,i,f,r){if(f&&(f.nodeType===3||f.nodeType===8))return;var e=c.type||c,o=[],p,s,j,g,h,l,k,m,n,q;if(M.test(e+a.event.triggered))return;if(e.indexOf("!")>=0&&(e=e.slice(0,-1),s=!0),e.indexOf(".")>=0&&(o=e.split("."),e=o.shift(),o.sort()),(!f||a.event.customEvent[e])&&!a.event.global[e])return;if(c=typeof c=="object"?c[a.expando]?c:new a.Event(e,c):new a.Event(e),c.type=e,c.isTrigger=!0,c.exclusive=s,c.namespace=o.join("."),c.namespace_re=c.namespace?new RegExp("(^|\\.)"+o.join("\\.(?:.*\\.)?")+"(\\.|$)"):null,l=e.indexOf(":")<0?"on"+e:"",!f){p=a.cache;for(j in p)p[j].events&&p[j].events[e]&&a.event.trigger(c,i,p[j].handle.elem,!0);return}if(c.result=b,c.target||(c.target=f),i=i!=null?a.makeArray(i):[],i.unshift(c),k=a.event.special[e]||{},k.trigger&&k.trigger.apply(f,i)===!1)return;if(n=[[f,k.bindType||e]],!r&&!k.noBubble&&!a.isWindow(f)){for(q=k.delegateType||e,g=M.test(q+e)?f:f.parentNode,h=null;g;g=g.parentNode)n.push([g,q]),h=g;h&&h===f.ownerDocument&&n.push([h.defaultView||h.parentWindow||d,q])}for(j=0;j<n.length&&!c.isPropagationStopped();j++)g=n[j][0],c.type=n[j][1],m=(a._data(g,"events")||{})[c.type]&&a._data(g,"handle"),m&&m.apply(g,i),m=l&&g[l],m&&a.acceptData(g)&&m.apply(g,i)===!1&&c.preventDefault();return c.type=e,!r&&!c.isDefaultPrevented()&&(!k._default||k._default.apply(f.ownerDocument,i)===!1)&&!(e==="click"&&a.nodeName(f,"a"))&&a.acceptData(f)&&l&&f[e]&&(e!=="focus"&&e!=="blur"||c.target.offsetWidth!==0)&&!a.isWindow(f)&&(h=f[l],h&&(f[l]=null),a.event.triggered=e,f[e](),a.event.triggered=b,h&&(f[l]=h)),c.result},dispatch:function(c){c=a.event.fix(c||d.event);var r=(a._data(this,"events")||{})[c.type]||[],m=r.delegateCount,s=[].slice.call(arguments,0),t=!c.exclusive&&!c.namespace,j=a.event.special[c.type]||{},k=[],f,l,g,n,o,p,i,q,e,h,u;if(s[0]=c,c.delegateTarget=this,j.preDispatch&&j.preDispatch.call(this,c)===!1)return;if(m&&!(c.button&&c.type==="click")){n=a(this),n.context=this.ownerDocument||this;for(g=c.target;g!=this;g=g.parentNode||this)if(g.disabled!==!0){p={},q=[],n[0]=g;for(f=0;f<m;f++)e=r[f],h=e.selector,p[h]===b&&(p[h]=e.quick?bg(g,e.quick):n.is(h)),p[h]&&q.push(e);q.length&&k.push({elem:g,matches:q})}}r.length>m&&k.push({elem:this,matches:r.slice(m)});for(f=0;f<k.length&&!c.isPropagationStopped();f++){i=k[f],c.currentTarget=i.elem;for(l=0;l<i.matches.length&&!c.isImmediatePropagationStopped();l++)e=i.matches[l],(t||!c.namespace&&!e.namespace||c.namespace_re&&c.namespace_re.test(e.namespace))&&(c.data=e.data,c.handleObj=e,o=((a.event.special[e.origType]||{}).handle||e.handler).apply(i.elem,s),o!==b&&(c.result=o,o===!1&&(c.preventDefault(),c.stopPropagation())))}return j.postDispatch&&j.postDispatch.call(this,c),c.result},props:"attrChange attrName relatedNode srcElement altKey bubbles cancelable ctrlKey currentTarget eventPhase metaKey relatedTarget shiftKey target timeStamp view which".split(" "),fixHooks:{},keyHooks:{props:"char charCode key keyCode".split(" "),filter:function(a,b){return a.which==null&&(a.which=b.charCode!=null?b.charCode:b.keyCode),a}},mouseHooks:{props:"button buttons clientX clientY fromElement offsetX offsetY pageX pageY screenX screenY toElement".split(" "),filter:function(a,f){var h,d,e,g=f.button,i=f.fromElement;return a.pageX==null&&f.clientX!=null&&(h=a.target.ownerDocument||c,d=h.documentElement,e=h.body,a.pageX=f.clientX+(d&&d.scrollLeft||e&&e.scrollLeft||0)-(d&&d.clientLeft||e&&e.clientLeft||0),a.pageY=f.clientY+(d&&d.scrollTop||e&&e.scrollTop||0)-(d&&d.clientTop||e&&e.clientTop||0)),!a.relatedTarget&&i&&(a.relatedTarget=i===a.target?f.toElement:i),!a.which&&g!==b&&(a.which=g&1?1:g&2?3:g&4?2:0),a}},fix:function(d){if(d[a.expando])return d;var g,h,e=d,f=a.event.fixHooks[d.type]||{},i=f.props?this.props.concat(f.props):this.props;d=a.Event(e);for(g=i.length;g;)h=i[--g],d[h]=e[h];return d.target||(d.target=e.srcElement||c),d.target.nodeType===3&&(d.target=d.target.parentNode),d.metaKey===b&&(d.metaKey=d.ctrlKey),f.filter?f.filter(d,e):d},special:{ready:{setup:a.bindReady},load:{noBubble:!0},focus:{delegateType:"focusin"},blur:{delegateType:"focusout"},beforeunload:{setup:function(c,d,b){a.isWindow(this)&&(this.onbeforeunload=b)},teardown:function(b,a){this.onbeforeunload===a&&(this.onbeforeunload=null)}}},simulate:function(e,c,d,f){var b=a.extend(new a.Event,d,{type:e,isSimulated:!0,originalEvent:{}});f?a.event.trigger(b,null,c):a.event.dispatch.call(c,b),b.isDefaultPrevented()&&d.preventDefault()}},a.event.handle=a.event.dispatch,a.removeEvent=c.removeEventListener?function(a,b,c){a.removeEventListener&&a.removeEventListener(b,c,!1)}:function(a,b,c){a.detachEvent&&a.detachEvent("on"+b,c)},a.Event=function(b,c){if(!(this instanceof a.Event))return new a.Event(b,c);b&&b.type?(this.originalEvent=b,this.type=b.type,this.isDefaultPrevented=b.defaultPrevented||b.returnValue===!1||b.getPreventDefault&&b.getPreventDefault()?q:m):this.type=b,c&&a.extend(this,c),this.timeStamp=b&&b.timeStamp||a.now(),this[a.expando]=!0};function m(){return!1}function q(){return!0}a.Event.prototype={preventDefault:function(){this.isDefaultPrevented=q;var a=this.originalEvent;if(!a)return;a.preventDefault?a.preventDefault():a.returnValue=!1},stopPropagation:function(){this.isPropagationStopped=q;var a=this.originalEvent;if(!a)return;a.stopPropagation&&a.stopPropagation(),a.cancelBubble=!0},stopImmediatePropagation:function(){this.isImmediatePropagationStopped=q,this.stopPropagation()},isDefaultPrevented:m,isPropagationStopped:m,isImmediatePropagationStopped:m},a.each({mouseenter:"mouseover",mouseleave:"mouseout"},function(c,b){a.event.special[c]={delegateType:b,bindType:b,handle:function(c){var f=this,d=c.relatedTarget,e=c.handleObj,h=e.selector,g;return(!d||d!==f&&!a.contains(f,d))&&(c.type=e.origType,g=e.handler.apply(this,arguments),c.type=b),g}}}),a.support.submitBubbles||(a.event.special.submit={setup:function(){if(a.nodeName(this,"form"))return!1;a.event.add(this,"click._submit keypress._submit",function(e){var d=e.target,c=a.nodeName(d,"input")||a.nodeName(d,"button")?d.form:b;c&&!c._submit_attached&&(a.event.add(c,"submit._submit",function(a){a._submit_bubble=!0}),c._submit_attached=!0)})},postDispatch:function(b){b._submit_bubble&&(delete b._submit_bubble,this.parentNode&&!b.isTrigger&&a.event.simulate("submit",this.parentNode,b,!0))},teardown:function(){if(a.nodeName(this,"form"))return!1;a.event.remove(this,"._submit")}}),a.support.changeBubbles||(a.event.special.change={setup:function(){if(A.test(this.nodeName))return(this.type==="checkbox"||this.type==="radio")&&(a.event.add(this,"propertychange._change",function(a){a.originalEvent.propertyName==="checked"&&(this._just_changed=!0)}),a.event.add(this,"click._change",function(b){this._just_changed&&!b.isTrigger&&(this._just_changed=!1,a.event.simulate("change",this,b,!0))})),!1;a.event.add(this,"beforeactivate._change",function(c){var b=c.target;A.test(b.nodeName)&&!b._change_attached&&(a.event.add(b,"change._change",function(b){this.parentNode&&!b.isSimulated&&!b.isTrigger&&a.event.simulate("change",this.parentNode,b,!0)}),b._change_attached=!0)})},handle:function(a){var b=a.target;if(this!==b||a.isSimulated||a.isTrigger||b.type!=="radio"&&b.type!=="checkbox")return a.handleObj.handler.apply(this,arguments)},teardown:function(){return a.event.remove(this,"._change"),A.test(this.nodeName)}}),a.support.focusinBubbles||a.each({focus:"focusin",blur:"focusout"},function(b,d){var e=0,f=function(b){a.event.simulate(d,b.target,a.event.fix(b),!0)};a.event.special[d]={setup:function(){e++===0&&c.addEventListener(b,f,!0)},teardown:function(){--e===0&&c.removeEventListener(b,f,!0)}}}),a.fn.extend({on:function(f,d,e,c,i){var g,h;if(typeof f=="object"){typeof d!="string"&&(e=e||d,d=b);for(h in f)this.on(h,d,e,f[h],i);return this}if(e==null&&c==null?(c=d,e=d=b):c==null&&(typeof d=="string"?(c=e,e=b):(c=e,e=d,d=b)),c===!1)c=m;else if(!c)return this;return i===1&&(g=c,c=function(b){return a().off(b),g.apply(this,arguments)},c.guid=g.guid||(g.guid=a.guid++)),this.each(function(){a.event.add(this,f,c,e,d)})},one:function(a,b,c,d){return this.on(a,b,c,d,1)},off:function(c,e,f){var d,g;if(c&&c.preventDefault&&c.handleObj)return d=c.handleObj,a(c.delegateTarget).off(d.namespace?d.origType+"."+d.namespace:d.origType,d.selector,d.handler),this;if(typeof c=="object"){for(g in c)this.off(g,e,c[g]);return this}return(e===!1||typeof e=="function")&&(f=e,e=b),f===!1&&(f=m),this.each(function(){a.event.remove(this,c,f,e)})},bind:function(a,b,c){return this.on(a,null,b,c)},unbind:function(a,b){return this.off(a,null,b)},live:function(b,c,d){return a(this.context).on(b,this.selector,c,d),this},die:function(b,c){return a(this.context).off(b,this.selector||"**",c),this},delegate:function(a,b,c,d){return this.on(b,a,c,d)},undelegate:function(a,b,c){return arguments.length==1?this.off(a,"**"):this.off(b,a,c)},trigger:function(b,c){return this.each(function(){a.event.trigger(b,c,this)})},triggerHandler:function(b,c){if(this[0])return a.event.trigger(b,c,this[0],!0)},toggle:function(b){var c=arguments,e=b.guid||a.guid++,d=0,f=function(f){var e=(a._data(this,"lastToggle"+b.guid)||0)%d;return a._data(this,"lastToggle"+b.guid,e+1),f.preventDefault(),c[e].apply(this,arguments)||!1};for(f.guid=e;d<c.length;)c[d++].guid=e;return this.click(f)},hover:function(a,b){return this.mouseenter(a).mouseleave(b||a)}}),a.each(("blur focus focusin focusout load resize scroll unload click dblclick mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave change select submit keydown keypress keyup error contextmenu").split(" "),function(c,b){a.fn[b]=function(a,c){return c==null&&(c=a,a=null),arguments.length>0?this.on(b,null,a,c):this.trigger(b)},a.attrFn&&(a.attrFn[b]=!0),bk.test(b)&&(a.event.fixHooks[b]=a.event.keyHooks),bj.test(b)&&(a.event.fixHooks[b]=a.event.mouseHooks)}),function(){var p=/((?:\((?:\([^()]+\)|[^()]+)+\)|\[(?:\[[^\[\]]*\]|['"][^'"]*['"]|[^\[\]'"]+)+\]|\\.|[^ >+~,(\[\\]+)+|[>+~])(\s*,\s*)?((?:.|\r|\n)*)/g,h="sizcache"+(Math.random()+'').replace('.',''),n=0,t=Object.prototype.toString,k=!1,s=!0,g=/\\/g,w=/\r\n/g,j=/\W/,d,o,e,r,x,l,f,m,i,v;[0,0].sort(function(){return s=!1,0}),d=function(l,b,i,o){var y,m,k,g,u,j,n,q,h,w,s,a,x;if(i=i||[],b=b||c,y=b,b.nodeType!==1&&b.nodeType!==9)return[];if(!l||typeof l!="string")return i;w=!0,s=d.isXML(b),a=[],x=l;do if(p.exec(""),m=p.exec(x),m){if(x=m[3],a.push(m[1]),m[2]){u=m[3];break}}while(m)if(a.length>1&&r.exec(l))if(a.length===2&&e.relative[a[0]])k=v(a[0]+a[1],b,o);else for(k=e.relative[a[0]]?[b]:d(a.shift(),b);a.length;)l=a.shift(),e.relative[l]&&(l+=a.shift()),k=v(l,k,o);else if(!o&&a.length>1&&b.nodeType===9&&!s&&e.match.ID.test(a[0])&&!e.match.ID.test(a[a.length-1])&&(j=d.find(a.shift(),b,s),b=j.expr?d.filter(j.expr,j.set)[0]:j.set[0]),b)for(j=o?{expr:a.pop(),set:f(o)}:d.find(a.pop(),a.length===1&&(a[0]==="~"||a[0]==="+")&&b.parentNode?b.parentNode:b,s),k=j.expr?d.filter(j.expr,j.set):j.set,a.length>0?g=f(k):w=!1;a.length;)n=a.pop(),q=n,e.relative[n]?q=a.pop():n="",q==null&&(q=b),e.relative[n](g,q,s);else g=a=[];if(g||(g=k),g||d.error(n||l),t.call(g)==="[object Array]")if(w){if(b&&b.nodeType===1){for(h=0;g[h]!=null;h++)g[h]&&(g[h]===!0||g[h].nodeType===1&&d.contains(b,g[h]))&&i.push(k[h])}else for(h=0;g[h]!=null;h++)g[h]&&g[h].nodeType===1&&i.push(k[h])}else i.push.apply(i,g);else f(g,i);return u&&(d(u,y,i,o),d.uniqueSort(i)),i},d.uniqueSort=function(a){if(m){if(k=s,a.sort(m),k)for(var b=1;b<a.length;b++)a[b]===a[b-1]&&a.splice(b--,1)}return a},d.matches=function(a,b){return d(a,null,null,b)},d.matchesSelector=function(a,b){return d(b,null,null,[a]).length>0},d.find=function(c,i,k){var b,f,j,a,d,h;if(!c)return[];for(f=0,j=e.order.length;f<j;f++)if(d=e.order[f],a=e.leftMatch[d].exec(c)){if(h=a[1],a.splice(1,1),h.substr(h.length-1)!=="\\"){if(a[1]=(a[1]||"").replace(g,""),b=e.find[d](a,i,k),b!=null){c=c.replace(e.match[d],"");break}}}return b||(b=typeof i.getElementsByTagName!="undefined"?i.getElementsByTagName("*"):[]),{set:b,expr:c}},d.filter=function(c,i,p,s){for(var a,h,g,l,m,q,n,k,o,r=c,j=[],f=i,t=i&&i[0]&&d.isXML(i[0]);c&&i.length;){for(g in e.filter)if((a=e.leftMatch[g].exec(c))!=null&&a[2]){if(q=e.filter[g],n=a[1],h=!1,a.splice(1,1),n.substr(n.length-1)==="\\")continue;if(f===j&&(j=[]),e.preFilter[g])if(a=e.preFilter[g](a,f,p,j,s,t),a){if(a===!0)continue}else h=l=!0;if(a)for(k=0;(m=f[k])!=null;k++)m&&(l=q(m,a,k,f),o=s^l,p&&l!=null?o?h=!0:f[k]=!1:o&&(j.push(m),h=!0));if(l!==b){if(p||(f=j),c=c.replace(e.match[g],""),!h)return[];break}}if(c===r)if(h==null)d.error(c);else break;r=c}return f},d.error=function(a){throw new Error("Syntax error, unrecognized expression: "+a)},o=d.getText=function(a){var c,d,b=a.nodeType,e="";if(b){if(b===1||b===9||b===11){if(typeof a.textContent=='string')return a.textContent;if(typeof a.innerText=='string')return a.innerText.replace(w,'');for(a=a.firstChild;a;a=a.nextSibling)e+=o(a)}else if(b===3||b===4)return a.nodeValue}else for(c=0;d=a[c];c++)d.nodeType!==8&&(e+=o(d));return e},e=d.selectors={order:["ID","NAME","TAG"],match:{ID:/#((?:[\w\u00c0-\uFFFF\-]|\\.)+)/,CLASS:/\.((?:[\w\u00c0-\uFFFF\-]|\\.)+)/,NAME:/\[name=['"]*((?:[\w\u00c0-\uFFFF\-]|\\.)+)['"]*\]/,ATTR:/\[\s*((?:[\w\u00c0-\uFFFF\-]|\\.)+)\s*(?:(\S?=)\s*(?:(['"])(.*?)\3|(#?(?:[\w\u00c0-\uFFFF\-]|\\.)*)|)|)\s*\]/,TAG:/^((?:[\w\u00c0-\uFFFF\*\-]|\\.)+)/,CHILD:/:(only|nth|last|first)-child(?:\(\s*(even|odd|(?:[+\-]?\d+|(?:[+\-]?\d*)?n\s*(?:[+\-]\s*\d+)?))\s*\))?/,POS:/:(nth|eq|gt|lt|first|last|even|odd)(?:\((\d*)\))?(?=[^\-]|$)/,PSEUDO:/:((?:[\w\u00c0-\uFFFF\-]|\\.)+)(?:\((['"]?)((?:\([^\)]+\)|[^\(\)]*)+)\2\))?/},leftMatch:{},attrMap:{class:"className",for:"htmlFor"},attrHandle:{href:function(a){return a.getAttribute("href")},type:function(a){return a.getAttribute("type")}},relative:{"+":function(c,b){var f=typeof b=="string",g=f&&!j.test(b),h=f&&!g,e,i,a;g&&(b=b.toLowerCase());for(e=0,i=c.length,a;e<i;e++)if(a=c[e]){while((a=a.previousSibling)&&a.nodeType!==1);c[e]=h||a&&a.nodeName.toLowerCase()===b?a||!1:a===b}h&&d.filter(b,c,!0)},">":function(e,b){var c,f=typeof b=="string",a=0,g=e.length,h;if(f&&!j.test(b)){for(b=b.toLowerCase();a<g;a++)c=e[a],c&&(h=c.parentNode,e[a]=h.nodeName.toLowerCase()===b&&h)}else{for(;a<g;a++)c=e[a],c&&(e[a]=f?c.parentNode:c.parentNode===b);f&&d.filter(b,e,!0)}},"":function(d,a,e){var b,f=n++,c=q;typeof a=="string"&&!j.test(a)&&(a=a.toLowerCase(),b=a,c=u),c("parentNode",a,f,d,b,e)},"~":function(d,a,e){var b,f=n++,c=q;typeof a=="string"&&!j.test(a)&&(a=a.toLowerCase(),b=a,c=u),c("previousSibling",a,f,d,b,e)}},find:{ID:function(c,b,d){if(typeof b.getElementById!="undefined"&&!d){var a=b.getElementById(c[1]);return a&&a.parentNode?[a]:[]}},NAME:function(d,e){var b,c,a,f;if(typeof e.getElementsByName!="undefined"){b=[],c=e.getElementsByName(d[1]);for(a=0,f=c.length;a<f;a++)c[a].getAttribute("name")===d[1]&&b.push(c[a]);return b.length===0?null:b}},TAG:function(b,a){if(typeof a.getElementsByTagName!="undefined")return a.getElementsByTagName(b[1])}},preFilter:{CLASS:function(b,e,d,f,h,i){if(b=" "+b[1].replace(g,"")+" ",i)return b;for(var c=0,a;(a=e[c])!=null;c++)a&&(h^(a.className&&(" "+a.className+" ").replace(/[\t\n\r]/g," ").indexOf(b)>=0)?d||f.push(a):d&&(e[c]=!1));return!1},ID:function(a){return a[1].replace(g,"")},TAG:function(a,b){return a[1].replace(g,"").toLowerCase()},CHILD:function(a){if(a[1]==="nth"){a[2]||d.error(a[0]),a[2]=a[2].replace(/^\+|\s*/g,'');var b=/(-?)(\d*)(?:n([+\-]?\d*))?/.exec(a[2]==="even"&&"2n"||a[2]==="odd"&&"2n+1"||!/\D/.test(a[2])&&"0n+"+a[2]||a[2]);a[2]=b[1]+(b[2]||1)-0,a[3]=b[3]-0}else a[2]&&d.error(a[0]);return a[0]=n++,a},ATTR:function(a,d,f,h,i,c){var b=a[1]=a[1].replace(g,"");return!c&&e.attrMap[b]&&(a[1]=e.attrMap[b]),a[4]=(a[4]||a[5]||"").replace(g,""),a[2]==="~="&&(a[4]=" "+a[4]+" "),a},PSEUDO:function(a,b,c,f,g){if(a[1]==="not")if((p.exec(a[3])||"").length>1||/^\w/.test(a[3]))a[3]=d(a[3],null,null,b);else{var h=d.filter(a[3],b,c,!0^g);return c||f.push.apply(f,h),!1}else if(e.match.POS.test(a[0])||e.match.CHILD.test(a[0]))return!0;return a},POS:function(a){return a.unshift(!0),a}},filters:{enabled:function(a){return a.disabled===!1&&a.type!=="hidden"},disabled:function(a){return a.disabled===!0},checked:function(a){return a.checked===!0},selected:function(a){return a.parentNode&&a.parentNode.selectedIndex,a.selected===!0},parent:function(a){return!!a.firstChild},empty:function(a){return!a.firstChild},has:function(a,c,b){return!!d(b[3],a).length},header:function(a){return/h\d/i.test(a.nodeName)},text:function(a){var b=a.getAttribute("type"),c=a.type;return a.nodeName.toLowerCase()==="input"&&"text"===c&&(b===c||b===null)},radio:function(a){return a.nodeName.toLowerCase()==="input"&&"radio"===a.type},checkbox:function(a){return a.nodeName.toLowerCase()==="input"&&"checkbox"===a.type},file:function(a){return a.nodeName.toLowerCase()==="input"&&"file"===a.type},password:function(a){return a.nodeName.toLowerCase()==="input"&&"password"===a.type},submit:function(a){var b=a.nodeName.toLowerCase();return(b==="input"||b==="button")&&"submit"===a.type},image:function(a){return a.nodeName.toLowerCase()==="input"&&"image"===a.type},reset:function(a){var b=a.nodeName.toLowerCase();return(b==="input"||b==="button")&&"reset"===a.type},button:function(a){var b=a.nodeName.toLowerCase();return b==="input"&&"button"===a.type||b==="button"},input:function(a){return/input|select|textarea|button/i.test(a.nodeName)},focus:function(a){return a===a.ownerDocument.activeElement}},setFilters:{first:function(b,a){return a===0},last:function(c,a,d,b){return a===b.length-1},even:function(b,a){return a%2===0},odd:function(b,a){return a%2===1},lt:function(c,a,b){return a<b[3]-0},gt:function(c,a,b){return a>b[3]-0},nth:function(c,a,b){return b[3]-0===a},eq:function(c,a,b){return b[3]-0===a}},filter:{PSEUDO:function(a,b,i,j){var c=b[1],g=e.filters[c],h,f,k;if(g)return g(a,i,b,j);if(c==="contains")return(a.textContent||a.innerText||o([a])||"").indexOf(b[3])>=0;if(c==="not"){h=b[3];for(f=0,k=h.length;f<k;f++)if(h[f]===a)return!1;return!0}d.error(c)},CHILD:function(b,e){var c,g,i,d,l,j,f,k=e[1],a=b;switch(k){case"only":case"first":while(a=a.previousSibling)if(a.nodeType===1)return!1;if(k==="first")return!0;a=b;case"last":while(a=a.nextSibling)if(a.nodeType===1)return!1;return!0;case"nth":if(c=e[2],g=e[3],c===1&&g===0)return!0;if(i=e[0],d=b.parentNode,d&&(d[h]!==i||!b.nodeIndex)){j=0;for(a=d.firstChild;a;a=a.nextSibling)a.nodeType===1&&(a.nodeIndex=++j);d[h]=i}return f=b.nodeIndex-g,c===0?f===0:f%c===0&&f/c>=0}},ID:function(a,b){return a.nodeType===1&&a.getAttribute("id")===b},TAG:function(a,b){return b==="*"&&a.nodeType===1||!!a.nodeName&&a.nodeName.toLowerCase()===b},CLASS:function(a,b){return(" "+(a.className||a.getAttribute("class"))+" ").indexOf(b)>-1},ATTR:function(g,i){var f=i[1],h=d.attr?d.attr(g,f):e.attrHandle[f]?e.attrHandle[f](g):g[f]!=null?g[f]:g.getAttribute(f),b=h+"",c=i[2],a=i[4];return h==null?c==="!=":!c&&d.attr?h!=null:c==="="?b===a:c==="*="?b.indexOf(a)>=0:c==="~="?(" "+b+" ").indexOf(a)>=0:a?c==="!="?b!==a:c==="^="?b.indexOf(a)===0:c==="$="?b.substr(b.length-a.length)===a:c==="|="&&(b===a||b.substr(0,a.length+1)===a+"-"):b&&h!==!1},POS:function(c,a,d,f){var g=a[2],b=e.setFilters[g];if(b)return b(c,d,a,f)}}},r=e.match.POS,x=function(b,a){return"\\"+(a-0+1)};for(l in e.match)e.match[l]=new RegExp(e.match[l].source+/(?![^\[]*\])(?![^\(]*\))/.source),e.leftMatch[l]=new RegExp(/(^(?:.|\r|\n)*?)/.source+e.match[l].source.replace(/\\(\d+)/g,x));e.match.globalPOS=r,f=function(a,b){return(a=Array.prototype.slice.call(a,0),b)?(b.push.apply(b,a),b):a};try{Array.prototype.slice.call(c.documentElement.childNodes,0)[0].nodeType}catch(a){f=function(a,d){var b=0,c=d||[],e;if(t.call(a)==="[object Array]")Array.prototype.push.apply(c,a);else if(typeof a.length=="number")for(e=a.length;b<e;b++)c.push(a[b]);else for(;a[b];b++)c.push(a[b]);return c}}c.documentElement.compareDocumentPosition?m=function(a,b){return a===b?(k=!0,0):!a.compareDocumentPosition||!b.compareDocumentPosition?a.compareDocumentPosition?-1:1:a.compareDocumentPosition(b)&4?-1:1}:(m=function(c,d){var j,l,e,f,g,h,b,a;if(c===d)return k=!0,0;if(c.sourceIndex&&d.sourceIndex)return c.sourceIndex-d.sourceIndex;if(e=[],f=[],g=c.parentNode,h=d.parentNode,b=g,g===h)return i(c,d);if(g){if(!h)return 1}else return-1;while(b)e.unshift(b),b=b.parentNode;for(b=h;b;)f.unshift(b),b=b.parentNode;j=e.length,l=f.length;for(a=0;a<j&&a<l;a++)if(e[a]!==f[a])return i(e[a],f[a]);return a===j?i(c,f[a],-1):i(e[a],d,1)},i=function(b,c,d){if(b===c)return d;for(var a=b.nextSibling;a;){if(a===c)return-1;a=a.nextSibling}return 1}),function(){var a=c.createElement("div"),f="script"+(new Date).getTime(),d=c.documentElement;a.innerHTML="<a name='"+f+"'/>",d.insertBefore(a,d.firstChild),c.getElementById(f)&&(e.find.ID=function(c,d,e){if(typeof d.getElementById!="undefined"&&!e){var a=d.getElementById(c[1]);return a?a.id===c[1]||typeof a.getAttributeNode!="undefined"&&a.getAttributeNode("id").nodeValue===c[1]?[a]:b:[]}},e.filter.ID=function(a,c){var b=typeof a.getAttributeNode!="undefined"&&a.getAttributeNode("id");return a.nodeType===1&&b&&b.nodeValue===c}),d.removeChild(a),d=a=null}(),function(){var a=c.createElement("div");a.appendChild(c.createComment("")),a.getElementsByTagName("*").length>0&&(e.find.TAG=function(c,e){var a=e.getElementsByTagName(c[1]),d,b;if(c[1]==="*"){d=[];for(b=0;a[b];b++)a[b].nodeType===1&&d.push(a[b]);a=d}return a}),a.innerHTML="<a href='#'></a>",a.firstChild&&typeof a.firstChild.getAttribute!="undefined"&&a.firstChild.getAttribute("href")!=="#"&&(e.attrHandle.href=function(a){return a.getAttribute("href",2)}),a=null}(),c.querySelectorAll&&function(){var b=d,a=c.createElement("div"),h="__sizzle__",g;if(a.innerHTML="<p class='TEST'></p>",a.querySelectorAll&&a.querySelectorAll(".TEST").length===0)return;d=function(j,a,i,q){var g,k,p,m,l,n,o;if(a=a||c,!q&&!d.isXML(a)){if(g=/^(\w+$)|^\.([\w\-]+$)|^#([\w\-]+$)/.exec(j),g&&(a.nodeType===1||a.nodeType===9)){if(g[1])return f(a.getElementsByTagName(j),i);if(g[2]&&e.find.CLASS&&a.getElementsByClassName)return f(a.getElementsByClassName(g[2]),i)}if(a.nodeType===9){if(j==="body"&&a.body)return f([a.body],i);if(g&&g[3])if(k=a.getElementById(g[3]),k&&k.parentNode){if(k.id===g[3])return f([k],i)}else return f([],i);try{return f(a.querySelectorAll(j),i)}catch(a){}}else if(a.nodeType===1&&a.nodeName.toLowerCase()!=="object"){p=a,m=a.getAttribute("id"),l=m||h,n=a.parentNode,o=/^\s*[+~]/.test(j),m?l=l.replace(/'/g,"\\$&"):a.setAttribute("id",l),o&&n&&(a=a.parentNode);try{if(!o||n)return f(a.querySelectorAll("[id='"+l+"'] "+j),i)}catch(a){}finally{m||p.removeAttribute("id")}}}return b(j,a,i,q)};for(g in b)d[g]=b[g];a=null}(),function(){var a=c.documentElement,b=a.matchesSelector||a.mozMatchesSelector||a.webkitMatchesSelector||a.msMatchesSelector,g,f;if(b){g=!b.call(c.createElement("div"),"div"),f=!1;try{b.call(c.documentElement,"[test!='']:sizzle")}catch(a){f=!0}d.matchesSelector=function(c,a){if(a=a.replace(/\=\s*([^'"\]]*)\s*\]/g,"='$1']"),!d.isXML(c))try{if(f||!e.match.PSEUDO.test(a)&&!/!=/.test(a)){var h=b.call(c,a);if(h||!g||c.document&&c.document.nodeType!==11)return h}}catch(a){}return d(a,null,null,[c]).length>0}}}(),function(){var a=c.createElement("div");if(a.innerHTML="<div class='test e'></div><div class='test'></div>",!a.getElementsByClassName||a.getElementsByClassName("e").length===0)return;if(a.lastChild.className="e",a.getElementsByClassName("e").length===1)return;e.order.splice(1,0,"CLASS"),e.find.CLASS=function(b,a,c){if(typeof a.getElementsByClassName!="undefined"&&!c)return a.getElementsByClassName(b[1])},a=null}();function u(e,g,f,c,k,i){for(var b=0,j=c.length,a,d;b<j;b++)if(a=c[b],a){for(d=!1,a=a[e];a;){if(a[h]===f){d=c[a.sizset];break}if(a.nodeType===1&&!i&&(a[h]=f,a.sizset=b),a.nodeName.toLowerCase()===g){d=a;break}a=a[e]}c[b]=d}}function q(g,f,i,c,l,j){for(var b=0,k=c.length,a,e;b<k;b++)if(a=c[b],a){for(e=!1,a=a[g];a;){if(a[h]===i){e=c[a.sizset];break}if(a.nodeType===1)if(j||(a[h]=i,a.sizset=b),typeof f!="string"){if(a===f){e=!0;break}}else if(d.filter(f,[a]).length>0){e=a;break}a=a[g]}c[b]=e}}c.documentElement.contains?d.contains=function(a,b){return a!==b&&(!a.contains||a.contains(b))}:c.documentElement.compareDocumentPosition?d.contains=function(a,b){return!!(a.compareDocumentPosition(b)&16)}:d.contains=function(){return!1},d.isXML=function(a){var b=(a?a.ownerDocument||a:0).documentElement;return!!b&&b.nodeName!=="HTML"},v=function(a,b,j){for(var f,g=[],h="",i=b.nodeType?[b]:b,c,k;f=e.match.PSEUDO.exec(a);)h+=f[0],a=a.replace(e.match.PSEUDO,"");a=e.relative[a]?a+"*":a;for(c=0,k=i.length;c<k;c++)d(a,i[c],g,j);return d.filter(h,g)},d.attr=a.attr,d.selectors.attrMap={},a.find=d,a.expr=d.selectors,a.expr[":"]=a.expr.filters,a.unique=d.uniqueSort,a.text=d.getText,a.isXMLDoc=d.isXML,a.contains=d.contains}(),be=/Until$/,bd=/^(?:parents|prevUntil|prevAll)/,a$=/,/,a_=/^.[^:#\[\.,]*$/,aZ=Array.prototype.slice,Q=a.expr.match.globalPOS,aY={children:!0,contents:!0,next:!0,prev:!0},a.fn.extend({find:function(g){var i=this,b,f,c,h,d,e;if(typeof g!="string")return a(g).filter(function(){for(b=0,f=i.length;b<f;b++)if(a.contains(i[b],this))return!0});c=this.pushStack("","find",g);for(b=0,f=this.length;b<f;b++)if(h=c.length,a.find(g,this[b],c),b>0)for(d=h;d<c.length;d++)for(e=0;e<h;e++)if(c[e]===c[d]){c.splice(d--,1);break}return c},has:function(c){var b=a(c);return this.filter(function(){for(var c=0,d=b.length;c<d;c++)if(a.contains(this,b[c]))return!0})},not:function(a){return this.pushStack(T(this,a,!1),"not",a)},filter:function(a){return this.pushStack(T(this,a,!0),"filter",a)},is:function(b){return!!b&&(typeof b=="string"?Q.test(b)?a(b,this.context).index(this[0])>=0:a.filter(b,this).length>0:this.filter(b).length>0)},closest:function(c,f){var e=[],d,g,b=this[0],h,i;if(a.isArray(c)){for(h=1;b&&b.ownerDocument&&b!==f;){for(d=0;d<c.length;d++)a(b).is(c[d])&&e.push({selector:c[d],elem:b,level:h});b=b.parentNode,h++}return e}i=Q.test(c)||typeof c!="string"?a(c,f||this.context):0;for(d=0,g=this.length;d<g;d++)for(b=this[d];b;){if(i?i.index(b)>-1:a.find.matchesSelector(b,c)){e.push(b);break}if(b=b.parentNode,!b||!b.ownerDocument||b===f||b.nodeType===11)break}return e=e.length>1?a.unique(e):e,this.pushStack(e,"closest",c)},index:function(b){return b?typeof b=="string"?a.inArray(this[0],a(b)):a.inArray(b.jquery?b[0]:b,this):this[0]&&this[0].parentNode?this.prevAll().length:-1},add:function(b,e){var d=typeof b=="string"?a(b,e):a.makeArray(b&&b.nodeType?[b]:b),c=a.merge(this.get(),d);return this.pushStack(S(d[0])||S(c[0])?c:a.unique(c))},andSelf:function(){return this.add(this.prevObject)}});function S(a){return!a||!a.parentNode||a.parentNode.nodeType===11}a.each({parent:function(b){var a=b.parentNode;return a&&a.nodeType!==11?a:null},parents:function(b){return a.dir(b,"parentNode")},parentsUntil:function(b,d,c){return a.dir(b,"parentNode",c)},next:function(b){return a.nth(b,2,"nextSibling")},prev:function(b){return a.nth(b,2,"previousSibling")},nextAll:function(b){return a.dir(b,"nextSibling")},prevAll:function(b){return a.dir(b,"previousSibling")},nextUntil:function(b,d,c){return a.dir(b,"nextSibling",c)},prevUntil:function(b,d,c){return a.dir(b,"previousSibling",c)},siblings:function(b){return a.sibling((b.parentNode||{}).firstChild,b)},children:function(b){return a.sibling(b.firstChild)},contents:function(b){return a.nodeName(b,"iframe")?b.contentDocument||b.contentWindow.document:a.makeArray(b.childNodes)}},function(b,c){a.fn[b]=function(f,e){var d=a.map(this,c,f);return be.test(b)||(e=f),e&&typeof e=="string"&&(d=a.filter(e,d)),d=this.length>1&&!aY[b]?a.unique(d):d,(this.length>1||a$.test(e))&&bd.test(b)&&(d=d.reverse()),this.pushStack(d,b,aZ.call(arguments).join(","))}}),a.extend({filter:function(b,c,d){return d&&(b=":not("+b+")"),c.length===1?a.find.matchesSelector(c[0],b)?[c[0]]:[]:a.find.matches(b,c)},dir:function(g,d,e){for(var f=[],c=g[d];c&&c.nodeType!==9&&(e===b||c.nodeType!==1||!a(c).is(e));)c.nodeType===1&&f.push(c),c=c[d];return f},nth:function(a,b,c,e){b=b||1;for(var d=0;a;a=a[c])if(a.nodeType===1&&++d===b)break;return a},sibling:function(a,c){for(var b=[];a;a=a.nextSibling)a.nodeType===1&&a!==c&&b.push(a);return b}});function T(c,b,d){if(b=b||0,a.isFunction(b))return a.grep(c,function(a,c){var e=!!b.call(a,c,a);return e===d});if(b.nodeType)return a.grep(c,function(a,c){return a===b===d});if(typeof b=="string"){var e=a.grep(c,function(a){return a.nodeType===1});if(a_.test(b))return a.filter(b,e,!d);b=a.filter(b,e)}return a.grep(c,function(c,e){return a.inArray(c,b)>=0===d})}function U(c){var b=V.split("|"),a=c.createDocumentFragment();if(a.createElement)while(b.length)a.createElement(b.pop());return a}V="abbr|article|aside|audio|bdi|canvas|data|datalist|details|figcaption|figure|footer|header|hgroup|mark|meter|nav|output|progress|section|summary|time|video",aX=/ jQuery\d+="(?:\d+|null)"/g,y=/^\s+/,Y=/<(?!area|br|col|embed|hr|img|input|link|meta|param)(([\w:]+)[^>]*)\/>/ig,Z=/<([\w:]+)/,aW=/<tbody/i,aV=/<|&#?\w+;/,aT=/<(?:script|style)/i,aS=/<(?:script|object|embed|option|style)/i,ac=new RegExp("<(?:"+V+")[\\s/>]","i"),ad=/checked\s*(?:[^=]|=\s*.checked.)/i,ae=/\/(java|ecma)script/i,aR=/^\s*<!(?:\[CDATA\[|\-\-)/,e={option:[1,"<select multiple='multiple'>","</select>"],legend:[1,"<fieldset>","</fieldset>"],thead:[1,"<table>","</table>"],tr:[2,"<table><tbody>","</tbody></table>"],td:[3,"<table><tbody><tr>","</tr></tbody></table>"],col:[2,"<table><tbody></tbody><colgroup>","</colgroup></table>"],area:[1,"<map>","</map>"],_default:[0,"",""]},z=U(c),e.optgroup=e.option,e.tbody=e.tfoot=e.colgroup=e.caption=e.thead,e.th=e.td,a.support.htmlSerialize||(e._default=[1,"div<div>","</div>"]),a.fn.extend({text:function(d){return a.access(this,function(d){return d===b?a.text(this):this.empty().append((this[0]&&this[0].ownerDocument||c).createTextNode(d))},null,d,arguments.length)},wrapAll:function(b){if(a.isFunction(b))return this.each(function(c){a(this).wrapAll(b.call(this,c))});if(this[0]){var c=a(b,this[0].ownerDocument).eq(0).clone(!0);this[0].parentNode&&c.insertBefore(this[0]),c.map(function(){for(var a=this;a.firstChild&&a.firstChild.nodeType===1;)a=a.firstChild;return a}).append(this)}return this},wrapInner:function(b){return a.isFunction(b)?this.each(function(c){a(this).wrapInner(b.call(this,c))}):this.each(function(){var c=a(this),d=c.contents();d.length?d.wrapAll(b):c.append(b)})},wrap:function(b){var c=a.isFunction(b);return this.each(function(d){a(this).wrapAll(c?b.call(this,d):b)})},unwrap:function(){return this.parent().each(function(){a.nodeName(this,"body")||a(this).replaceWith(this.childNodes)}).end()},append:function(){return this.domManip(arguments,!0,function(a){this.nodeType===1&&this.appendChild(a)})},prepend:function(){return this.domManip(arguments,!0,function(a){this.nodeType===1&&this.insertBefore(a,this.firstChild)})},before:function(){if(this[0]&&this[0].parentNode)return this.domManip(arguments,!1,function(a){this.parentNode.insertBefore(a,this)});if(arguments.length){var b=a.clean(arguments);return b.push.apply(b,this.toArray()),this.pushStack(b,"before",arguments)}},after:function(){if(this[0]&&this[0].parentNode)return this.domManip(arguments,!1,function(a){this.parentNode.insertBefore(a,this.nextSibling)});if(arguments.length){var b=this.pushStack(this,"after",arguments);return b.push.apply(b,a.clean(arguments)),b}},remove:function(c,e){for(var d=0,b;(b=this[d])!=null;d++)(!c||a.filter(c,[b]).length)&&(!e&&b.nodeType===1&&(a.cleanData(b.getElementsByTagName("*")),a.cleanData([b])),b.parentNode&&b.parentNode.removeChild(b));return this},empty:function(){for(var c=0,b;(b=this[c])!=null;c++)for(b.nodeType===1&&a.cleanData(b.getElementsByTagName("*"));b.firstChild;)b.removeChild(b.firstChild);return this},clone:function(b,c){return b=b!=null&&b,c=c==null?b:c,this.map(function(){return a.clone(this,b,c)})},html:function(c){return a.access(this,function(c){var d=this[0]||{},f=0,g=this.length;if(c===b)return d.nodeType===1?d.innerHTML.replace(aX,""):null;if(typeof c=="string"&&!aT.test(c)&&(a.support.leadingWhitespace||!y.test(c))&&!e[(Z.exec(c)||["",""])[1].toLowerCase()]){c=c.replace(Y,"<$1></$2>");try{for(;f<g;f++)d=this[f]||{},d.nodeType===1&&(a.cleanData(d.getElementsByTagName("*")),d.innerHTML=c);d=0}catch(a){}}d&&this.empty().append(c)},null,c,arguments.length)},replaceWith:function(b){return this[0]&&this[0].parentNode?a.isFunction(b)?this.each(function(d){var c=a(this),e=c.html();c.replaceWith(b.call(this,d,e))}):(typeof b!="string"&&(b=a(b).detach()),this.each(function(){var c=this.nextSibling,d=this.parentNode;a(this).remove(),c?a(c).before(b):a(d).append(b)})):this.length?this.pushStack(a(a.isFunction(b)?b():b),"replaceWith",b):this},detach:function(a){return this.remove(a,!0)},domManip:function(f,d,k){var j,h,c,i,e=f[0],l=[],g,m,n;if(!a.support.checkClone&&arguments.length===3&&typeof e=="string"&&ad.test(e))return this.each(function(){a(this).domManip(f,d,k,!0)});if(a.isFunction(e))return this.each(function(g){var c=a(this);f[0]=e.call(this,g,d?c.html():b),c.domManip(f,d,k)});if(this[0]){if(i=e&&e.parentNode,a.support.parentNode&&i&&i.nodeType===11&&i.childNodes.length===this.length?j={fragment:i}:j=a.buildFragment(f,this,l),c=j.fragment,c.childNodes.length===1?h=c=c.firstChild:h=c.firstChild,h){d=d&&a.nodeName(h,"tr");for(g=0,m=this.length,n=m-1;g<m;g++)k.call(d?aP(this[g],h):this[g],j.cacheable||m>1&&g<n?a.clone(c,!0,!0):c)}l.length&&a.each(l,function(c,b){b.src?a.ajax({type:"GET",global:!1,url:b.src,async:!1,dataType:"script"}):a.globalEval((b.text||b.textContent||b.innerHTML||"").replace(aR,"/*$0*/")),b.parentNode&&b.parentNode.removeChild(b)})}return this}});function aP(b,c){return a.nodeName(b,"table")?b.getElementsByTagName("tbody")[0]||b.appendChild(b.ownerDocument.createElement("tbody")):b}function aj(i,f){if(f.nodeType!==1||!a.hasData(i))return;var d,e,g,h=a._data(i),b=a._data(f,h),c=h.events;if(c){delete b.handle,b.events={};for(d in c)for(e=0,g=c[d].length;e<g;e++)a.event.add(f,d,c[d][e])}b.data&&(b.data=a.extend({},b.data))}function ak(c,b){var d;if(b.nodeType!==1)return;b.clearAttributes&&b.clearAttributes(),b.mergeAttributes&&b.mergeAttributes(c),d=b.nodeName.toLowerCase(),d==="object"?b.outerHTML=c.outerHTML:d==="input"&&(c.type==="checkbox"||c.type==="radio")?(c.checked&&(b.defaultChecked=b.checked=c.checked),b.value!==c.value&&(b.value=c.value)):d==="option"?b.selected=c.defaultSelected:d==="input"||d==="textarea"?b.defaultValue=c.defaultValue:d==="script"&&b.text!==c.text&&(b.text=c.text),b.removeAttribute(a.expando),b.removeAttribute("_submit_attached"),b.removeAttribute("_change_attached")}a.buildFragment=function(i,g,j){var e,h,f,d,b=i[0];return g&&g[0]&&(d=g[0].ownerDocument||g[0]),d.createDocumentFragment||(d=c),i.length===1&&typeof b=="string"&&b.length<512&&d===c&&b.charAt(0)==="<"&&!aS.test(b)&&(a.support.checkClone||!ad.test(b))&&(a.support.html5Clone||!ac.test(b))&&(h=!0,f=a.fragments[b],f&&f!==1&&(e=f)),e||(e=d.createDocumentFragment(),a.clean(i,d,e,j)),h&&(a.fragments[b]=f?e:1),{fragment:e,cacheable:h}},a.fragments={},a.each({appendTo:"append",prependTo:"prepend",insertBefore:"before",insertAfter:"after",replaceAll:"replaceWith"},function(b,c){a.fn[b]=function(j){var f=[],d=a(j),g=this.length===1&&this[0].parentNode,e,i,h;if(g&&g.nodeType===11&&g.childNodes.length===1&&d.length===1)return d[c](this[0]),this;for(e=0,i=d.length;e<i;e++)h=(e>0?this.clone(!0):this).get(),a(d[e])[c](h),f=f.concat(h);return this.pushStack(f,b,d.selector)}});function p(a){return typeof a.getElementsByTagName!="undefined"?a.getElementsByTagName("*"):typeof a.querySelectorAll!="undefined"?a.querySelectorAll("*"):[]}function aw(a){(a.type==="checkbox"||a.type==="radio")&&(a.defaultChecked=a.checked)}function an(b){var c=(b.nodeName||"").toLowerCase();c==="input"?aw(b):c!=="script"&&typeof b.getElementsByTagName!="undefined"&&a.grep(b.getElementsByTagName("input"),aw)}function aM(b){var a=c.createElement("div");return z.appendChild(a),a.innerHTML=b.outerHTML,a.firstChild}a.extend({clone:function(b,g,h){var d,e,c,f=a.support.html5Clone||a.isXMLDoc(b)||!ac.test("<"+b.nodeName+">")?b.cloneNode(!0):aM(b);if((!a.support.noCloneEvent||!a.support.noCloneChecked)&&(b.nodeType===1||b.nodeType===11)&&!a.isXMLDoc(b)){ak(b,f),d=p(b),e=p(f);for(c=0;d[c];++c)e[c]&&ak(d[c],e[c])}if(g){if(aj(b,f),h){d=p(b),e=p(f);for(c=0;d[c];++c)aj(d[c],e[c])}}return d=e=null,f},clean:function(u,f,s,r){var o,h,g,i=[],j,b,p,m,v,d,n,k,t,l,q,w;f=f||c,typeof f.createElement=="undefined"&&(f=f.ownerDocument||f[0]&&f[0].ownerDocument||c);for(j=0,b;(b=u[j])!=null;j++){if(typeof b=="number"&&(b+=""),!b)continue;if(typeof b=="string")if(aV.test(b)){for(b=b.replace(Y,"<$1></$2>"),p=(Z.exec(b)||["",""])[1].toLowerCase(),m=e[p]||e._default,v=m[0],d=f.createElement("div"),n=z.childNodes,f===c?z.appendChild(d):U(f).appendChild(d),d.innerHTML=m[1]+b+m[2];v--;)d=d.lastChild;if(!a.support.tbody){t=aW.test(b),l=p==="table"&&!t?d.firstChild&&d.firstChild.childNodes:m[1]==="<table>"&&!t?d.childNodes:[];for(g=l.length-1;g>=0;--g)a.nodeName(l[g],"tbody")&&!l[g].childNodes.length&&l[g].parentNode.removeChild(l[g])}!a.support.leadingWhitespace&&y.test(b)&&d.insertBefore(f.createTextNode(y.exec(b)[0]),d.firstChild),b=d.childNodes,d&&(d.parentNode.removeChild(d),n.length>0&&(k=n[n.length-1],k&&k.parentNode&&k.parentNode.removeChild(k)))}else b=f.createTextNode(b);if(!a.support.appendChecked)if(b[0]&&typeof(q=b.length)=="number")for(g=0;g<q;g++)an(b[g]);else an(b);b.nodeType?i.push(b):i=a.merge(i,b)}if(s){o=function(a){return!a.type||ae.test(a.type)};for(j=0;i[j];j++)h=i[j],r&&a.nodeName(h,"script")&&(!h.type||ae.test(h.type))?r.push(h.parentNode?h.parentNode.removeChild(h):h):(h.nodeType===1&&(w=a.grep(h.getElementsByTagName("script"),o),i.splice.apply(i,[j+1,0].concat(w))),s.appendChild(h))}return i},cleanData:function(j){for(var f=0,d,g=a.cache,h=a.event.special,i=a.support.deleteExpando,c,b,e;(b=j[f])!=null;f++){if(b.nodeName&&a.noData[b.nodeName.toLowerCase()])continue;if(d=b[a.expando],d){if(c=g[d],c&&c.events){for(e in c.events)h[e]?a.event.remove(b,e):a.removeEvent(b,e,c.handle);c.handle&&(c.handle.elem=null)}i?delete b[a.expando]:b.removeAttribute&&b.removeAttribute(a.expando),delete g[d]}}}}),H=/alpha\([^)]*\)/i,aF=/opacity=([^)]*)/,aC=/([A-Z]|^ms)/g,aB=/^[\-+]?(?:\d*\.)?\d+$/i,E=/^-?(?:\d*\.)?\d+(?!px)[^\d\s]+$/i,aA=/^([\-+])=([\-+.\de]+)/,az=/^margin/,ay={position:"absolute",visibility:"hidden",display:"block"},h=["Top","Right","Bottom","Left"],a.fn.css=function(c,d){return a.access(this,function(c,d,e){return e!==b?a.style(c,d,e):a.css(c,d)},c,d,arguments.length>1)},a.extend({cssHooks:{opacity:{get:function(a,c){if(c){var b=n(a,"opacity");return b===""?"1":b}return a.style.opacity}}},cssNumber:{fillOpacity:!0,fontWeight:!0,lineHeight:!0,opacity:!0,orphans:!0,widows:!0,zIndex:!0,zoom:!0},cssProps:{float:a.support.cssFloat?"cssFloat":"styleFloat"},style:function(d,f,c,k){if(!d||d.nodeType===3||d.nodeType===8||!d.style)return;var g,h,i=a.camelCase(f),j=d.style,e=a.cssHooks[i];if(f=a.cssProps[i]||i,c!==b){{if(h=typeof c,h==="string"&&(g=aA.exec(c))&&(c=+(g[1]+1)*+g[2]+parseFloat(a.css(d,f)),h="number"),c==null||h==="number"&&isNaN(c))return;if(h==="number"&&!a.cssNumber[i]&&(c+="px"),!e||!("set"in e)||(c=e.set(d,c))!==b)try{j[f]=c}catch(a){}}}else return e&&"get"in e&&(g=e.get(d,!1,k))!==b?g:j[f]},css:function(e,c,g){var f,d;if(c=a.camelCase(c),d=a.cssHooks[c],c=a.cssProps[c]||c,c==="cssFloat"&&(c="float"),d&&"get"in d&&(f=d.get(e,!0,g))!==b)return f;if(n)return n(e,c)},swap:function(b,c,f){var d={},e,a;for(a in c)d[a]=b.style[a],b.style[a]=c[a];e=f.call(b);for(a in c)b.style[a]=d[a];return e}}),a.curCSS=a.css,c.defaultView&&c.defaultView.getComputedStyle&&(av=function(c,d){var b,g,e,h,f=c.style;return d=d.replace(aC,"-$1").toLowerCase(),(g=c.ownerDocument.defaultView)&&(e=g.getComputedStyle(c,null))&&(b=e.getPropertyValue(d),b===""&&!a.contains(c.ownerDocument.documentElement,c)&&(b=a.style(c,d))),!a.support.pixelMargin&&e&&az.test(d)&&E.test(b)&&(h=f.width,f.width=b,b=e.width,f.width=h),b}),c.documentElement.currentStyle&&(at=function(a,e){var f,d,g,b=a.currentStyle&&a.currentStyle[e],c=a.style;return b==null&&c&&(g=c[e])&&(b=g),E.test(b)&&(f=c.left,d=a.runtimeStyle&&a.runtimeStyle.left,d&&(a.runtimeStyle.left=a.currentStyle.left),c.left=e==="fontSize"?"1em":b,b=c.pixelLeft+"px",c.left=f,d&&(a.runtimeStyle.left=d)),b===""?"auto":b}),n=av||at;function ar(c,f,e){var b=f==="width"?c.offsetWidth:c.offsetHeight,d=f==="width"?1:0,g=4;if(b>0){if(e!=="border")for(;d<g;d+=2)e||(b-=parseFloat(a.css(c,"padding"+h[d]))||0),e==="margin"?b+=parseFloat(a.css(c,e+h[d]))||0:b-=parseFloat(a.css(c,"border"+h[d]+"Width"))||0;return b+"px"}if(b=n(c,f),(b<0||b==null)&&(b=c.style[f]),E.test(b))return b;if(b=parseFloat(b)||0,e)for(;d<g;d+=2)b+=parseFloat(a.css(c,"padding"+h[d]))||0,e!=="padding"&&(b+=parseFloat(a.css(c,"border"+h[d]+"Width"))||0),e==="margin"&&(b+=parseFloat(a.css(c,e+h[d]))||0);return b+"px"}a.each(["height","width"],function(c,b){a.cssHooks[b]={get:function(c,e,d){if(e)return c.offsetWidth!==0?ar(c,b,d):a.swap(c,ay,function(){return ar(c,b,d)})},set:function(b,a){return aB.test(a)?a+"px":a}}}),a.support.opacity||(a.cssHooks.opacity={get:function(a,b){return aF.test((b&&a.currentStyle?a.currentStyle.filter:a.style.filter)||"")?parseFloat(RegExp.$1)/100+"":b?"1":""},set:function(f,e){var b=f.style,c=f.currentStyle,g=a.isNumeric(e)?"alpha(opacity="+e*100+")":"",d=c&&c.filter||b.filter||"";if(b.zoom=1,e>=1&&a.trim(d.replace(H,""))===""){if(b.removeAttribute("filter"),c&&!c.filter)return}b.filter=H.test(d)?d.replace(H,g):d+" "+g}}),a(function(){a.support.reliableMarginRight||(a.cssHooks.marginRight={get:function(b,c){return a.swap(b,{display:"inline-block"},function(){return c?n(b,"margin-right"):b.style.marginRight})}})}),a.expr&&a.expr.filters&&(a.expr.filters.hidden=function(b){var c=b.offsetWidth,d=b.offsetHeight;return c===0&&d===0||!a.support.reliableHiddenOffsets&&(b.style&&b.style.display||a.css(b,"display"))==="none"},a.expr.filters.visible=function(b){return!a.expr.filters.hidden(b)}),a.each({margin:"",padding:"",border:"Width"},function(b,c){a.cssHooks[b+c]={expand:function(d){var a,e=typeof d=="string"?d.split(" "):[d],f={};for(a=0;a<4;a++)f[b+h[a]+c]=e[a]||e[a-2]||e[0];return f}}}),aD=/%20/g,aE=/\[\]$/,aq=/\r?\n/g,aG=/#.*$/,aH=/^(.*?):[ \t]*([^\r\n]*)\r?$/mg,aI=/^(?:color|date|datetime|datetime-local|email|hidden|month|number|password|range|search|tel|text|time|url|week)$/i,aJ=/^(?:about|app|app\-storage|.+\-extension|file|res|widget):$/,aK=/^(?:GET|HEAD)$/,aL=/^\/\//,ao=/\?/,aN=/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi,aO=/^(?:select|textarea)/i,ai=/\s+/,aQ=/([?&])_=[^&]*/,af=/^([\w\+\.\-]+:)(?:\/\/([^\/?#:]*)(?::(\d+))?)?/,ab=a.fn.load,G={},ax={},W=["*/"]+["*"];try{j=bv.href}catch(a){j=c.createElement("a"),j.href="",j=j.href}i=af.exec(j.toLowerCase())||[];function R(b){return function(d,e){if(typeof d!="string"&&(e=d,d="*"),a.isFunction(e))for(var h=d.toLowerCase().split(ai),f=0,j=h.length,c,i,g;f<j;f++)c=h[f],g=/^\+/.test(c),g&&(c=c.substr(1)||"*"),i=b[c]=b[c]||[],i[g?"unshift":"push"](e)}}function u(e,d,g,h,f,c){f=f||d.dataTypes[0],c=c||{},c[f]=!0;for(var i=e[f],j=0,l=i?i.length:0,k=e===G,a;j<l&&(k||!a);j++)a=i[j](d,g,h),typeof a=="string"&&(!k||c[a]?a=b:(d.dataTypes.unshift(a),a=u(e,d,g,h,a,c)));return(k||!a)&&!c["*"]&&(a=u(e,d,g,h,"*",c)),a}function O(f,e){var c,d,g=a.ajaxSettings.flatOptions||{};for(c in e)e[c]!==b&&((g[c]?f:d||(d={}))[c]=e[c]);d&&a.extend(!0,f,d)}a.fn.extend({load:function(d,c,f){var e,g,h,i;return typeof d!="string"&&ab?ab.apply(this,arguments):this.length?(e=d.indexOf(" "),e>=0&&(g=d.slice(e,d.length),d=d.slice(0,e)),h="GET",c&&(a.isFunction(c)?(f=c,c=b):typeof c=="object"&&(c=a.param(c,a.ajaxSettings.traditional),h="POST")),i=this,a.ajax({url:d,type:h,dataType:"html",data:c,complete:function(c,d,b){b=c.responseText,c.isResolved()&&(c.done(function(a){b=a}),i.html(g?a("<div>").append(b.replace(aN,"")).find(g):b)),f&&i.each(f,[b,d,c])}}),this):this},serialize:function(){return a.param(this.serializeArray())},serializeArray:function(){return this.map(function(){return this.elements?a.makeArray(this.elements):this}).filter(function(){return this.name&&!this.disabled&&(this.checked||aO.test(this.nodeName)||aI.test(this.type))}).map(function(d,c){var b=a(this).val();return b==null?null:a.isArray(b)?a.map(b,function(a,b){return{name:c.name,value:a.replace(aq,"\r\n")}}):{name:c.name,value:b.replace(aq,"\r\n")}}).get()}}),a.each("ajaxStart ajaxStop ajaxComplete ajaxError ajaxSuccess ajaxSend".split(" "),function(c,b){a.fn[b]=function(a){return this.on(b,a)}}),a.each(["get","post"],function(d,c){a[c]=function(g,d,e,f){return a.isFunction(d)&&(f=f||e,e=d,d=b),a.ajax({type:c,url:g,data:d,success:e,dataType:f})}}),a.extend({getScript:function(c,d){return a.get(c,b,d,"script")},getJSON:function(b,c,d){return a.get(b,c,d,"json")},ajaxSetup:function(b,c){return c?O(b,a.ajaxSettings):(c=b,b=a.ajaxSettings),O(b,c),b},ajaxSettings:{url:j,isLocal:aJ.test(i[1]),global:!0,type:"GET",contentType:"application/x-www-form-urlencoded; charset=UTF-8",processData:!0,async:!0,accepts:{xml:"application/xml, text/xml",html:"text/html",text:"text/plain",json:"application/json, text/javascript","*":W},contents:{xml:/xml/,html:/html/,json:/json/},responseFields:{xml:"responseXML",text:"responseText"},converters:{"* text":d.String,"text html":!0,"text json":a.parseJSON,"text xml":a.parseXML},flatOptions:{context:!0,url:!0}},ajaxPrefilter:R(G),ajaxTransport:R(ax),ajax:function(v,h){var c,g,t,s,y,n,f,x,w,p,o,k,r,j,e,m,l,d,z,A;typeof v=="object"&&(h=v,v=b),h=h||{},c=a.ajaxSetup({},h),g=c.context||c,t=g!==c&&(g.nodeType||g instanceof a)?a(g):a.event,s=a.Deferred(),y=a.Callbacks("once memory"),n=c.statusCode||{},x={},w={},e=0,d={readyState:0,setRequestHeader:function(a,c){if(!e){var b=a.toLowerCase();a=w[b]=w[b]||a,x[a]=c}return this},getAllResponseHeaders:function(){return e===2?p:null},getResponseHeader:function(c){var a;if(e===2){if(!o)for(o={};a=aH.exec(p);)o[a[1].toLowerCase()]=a[2];a=o[c.toLowerCase()]}return a===b?null:a},overrideMimeType:function(a){return e||(c.mimeType=a),this},abort:function(a){return a=a||"abort",k&&k.abort(a),q(0,a),this}};function q(i,v,q,z){if(e===2)return;e=2,r&&clearTimeout(r),k=b,p=z||"",d.readyState=i>0?4:0;var j,o,l,h=v,x=q?ba(c,d,q):b,u,w;if(i>=200&&i<300||i===304)if(c.ifModified&&((u=d.getResponseHeader("Last-Modified"))&&(a.lastModified[f]=u),(w=d.getResponseHeader("Etag"))&&(a.etag[f]=w)),i===304)h="notmodified",j=!0;else try{o=bb(c,x),h="success",j=!0}catch(a){h="parsererror",l=a}else l=h,(!h||i)&&(h="error",i<0&&(i=0));d.status=i,d.statusText=""+(v||h),j?s.resolveWith(g,[o,h,d]):s.rejectWith(g,[d,h,l]),d.statusCode(n),n=b,m&&t.trigger("ajax"+(j?"Success":"Error"),[d,c,j?o:l]),y.fireWith(g,[d,h]),m&&(t.trigger("ajaxComplete",[d,c]),--a.active||a.event.trigger("ajaxStop"))}if(s.promise(d),d.success=d.done,d.error=d.fail,d.complete=y.add,d.statusCode=function(b){if(b){var a;if(e<2)for(a in b)n[a]=[n[a],b[a]];else a=b[d.status],d.then(a,a)}return this},c.url=((v||c.url)+"").replace(aG,"").replace(aL,i[1]+"//"),c.dataTypes=a.trim(c.dataType||"*").toLowerCase().split(ai),c.crossDomain==null&&(j=af.exec(c.url.toLowerCase()),c.crossDomain=!!(j&&(j[1]!=i[1]||j[2]!=i[2]||(j[3]||(j[1]==="http:"?80:443))!=(i[3]||(i[1]==="http:"?80:443))))),c.data&&c.processData&&typeof c.data!="string"&&(c.data=a.param(c.data,c.traditional)),u(G,c,h,d),e===2)return!1;m=c.global,c.type=c.type.toUpperCase(),c.hasContent=!aK.test(c.type),m&&a.active++===0&&a.event.trigger("ajaxStart"),c.hasContent||(c.data&&(c.url+=(ao.test(c.url)?"&":"?")+c.data,delete c.data),f=c.url,c.cache===!1&&(z=a.now(),A=c.url.replace(aQ,"$1_="+z),c.url=A+(A===c.url?(ao.test(c.url)?"&":"?")+"_="+z:""))),(c.data&&c.hasContent&&c.contentType!==!1||h.contentType)&&d.setRequestHeader("Content-Type",c.contentType),c.ifModified&&(f=f||c.url,a.lastModified[f]&&d.setRequestHeader("If-Modified-Since",a.lastModified[f]),a.etag[f]&&d.setRequestHeader("If-None-Match",a.etag[f])),d.setRequestHeader("Accept",c.dataTypes[0]&&c.accepts[c.dataTypes[0]]?c.accepts[c.dataTypes[0]]+(c.dataTypes[0]!=="*"?", "+W+"; q=0.01":""):c.accepts["*"]);for(l in c.headers)d.setRequestHeader(l,c.headers[l]);if(c.beforeSend&&(c.beforeSend.call(g,d,c)===!1||e===2))return d.abort(),!1;for(l in{success:1,error:1,complete:1})d[l](c[l]);if(k=u(ax,c,h,d),k){d.readyState=1,m&&t.trigger("ajaxSend",[d,c]),c.async&&c.timeout>0&&(r=setTimeout(function(){d.abort("timeout")},c.timeout));try{e=1,k.send(x,q)}catch(a){if(e<2)q(-1,a);else throw a}}else q(-1,"No Transport");return d},param:function(c,d){var e=[],f=function(c,b){b=a.isFunction(b)?b():b,e[e.length]=encodeURIComponent(c)+"="+encodeURIComponent(b)},g;if(d===b&&(d=a.ajaxSettings.traditional),a.isArray(c)||c.jquery&&!a.isPlainObject(c))a.each(c,function(){f(this.name,this.value)});else for(g in c)B(g,c[g],d,f);return e.join("&").replace(aD,"+")}});function B(c,b,d,e){if(a.isArray(b))a.each(b,function(b,a){d||aE.test(c)?e(c,a):B(c+"["+(typeof a=="object"?b:"")+"]",a,d,e)});else if(!d&&a.type(b)==="object")for(var f in b)B(c+"["+f+"]",b[f],d,e);else e(c,b)}a.extend({active:0,lastModified:{},etag:{}});function ba(e,k,f){var h=e.contents,c=e.dataTypes,j=e.responseFields,g,a,d,i;for(a in j)a in f&&(k[j[a]]=f[a]);while(c[0]==="*")c.shift(),g===b&&(g=e.mimeType||k.getResponseHeader("content-type"));if(g)for(a in h)if(h[a]&&h[a].test(g)){c.unshift(a);break}if(c[0]in f)d=c[0];else{for(a in f){if(!c[0]||e.converters[a+" "+c[0]]){d=a;break}i||(i=a)}d=d||i}if(d)return d!==c[0]&&c.unshift(d),f[d]}function bb(j,g){j.dataFilter&&(g=j.dataFilter(g,j.dataType));var o=j.dataTypes,h={},k,l,p=o.length,m,c=o[0],i,n,d,e,f;for(k=1;k<p;k++){if(k===1)for(l in j.converters)typeof l=="string"&&(h[l.toLowerCase()]=j.converters[l]);if(i=c,c=o[k],c==="*")c=i;else if(i!=="*"&&i!==c){if(n=i+" "+c,d=h[n]||h["* "+c],!d){f=b;for(e in h)if(m=e.split(" "),m[0]===i||m[0]==="*"){if(f=h[m[1]+" "+c],f){e=h[e],e===!0?d=f:f===!0&&(d=e);break}}}d||f||a.error("No conversion from "+n.replace(" "," to ")),d!==!0&&(g=d?d(g):f(e(g)))}}return g}bc=a.now(),r=/(\=)\?(&|$)|\?\?/i,a.ajaxSetup({jsonp:"callback",jsonpCallback:function(){return a.expando+"_"+bc++}}),a.ajaxPrefilter("json jsonp",function(b,l,k){var h=typeof b.data=="string"&&/^application\/x\-www\-form\-urlencoded/.test(b.contentType),f,c,i,e,g,j;if(b.dataTypes[0]==="jsonp"||b.jsonp!==!1&&(r.test(b.url)||h&&r.test(b.data)))return c=b.jsonpCallback=a.isFunction(b.jsonpCallback)?b.jsonpCallback():b.jsonpCallback,i=d[c],e=b.url,g=b.data,j="$1"+c+"$2",b.jsonp!==!1&&(e=e.replace(r,j),b.url===e&&(h&&(g=g.replace(r,j)),b.data===g&&(e+=(/\?/.test(e)?"&":"?")+b.jsonp+"="+c))),b.url=e,b.data=g,d[c]=function(a){f=[a]},k.always(function(){d[c]=i,f&&a.isFunction(i)&&d[c](f[0])}),b.converters["script json"]=function(){return f||a.error(c+" was not called"),f[0]},b.dataTypes[0]="json","script"}),a.ajaxSetup({accepts:{script:"text/javascript, application/javascript, application/ecmascript, application/x-ecmascript"},contents:{script:/javascript|ecmascript/},converters:{"text script":function(b){return a.globalEval(b),b}}}),a.ajaxPrefilter("script",function(a){a.cache===b&&(a.cache=!1),a.crossDomain&&(a.type="GET",a.global=!1)}),a.ajaxTransport("script",function(d){if(d.crossDomain){var a,e=c.head||c.getElementsByTagName("head")[0]||c.documentElement;return{send:function(g,f){a=c.createElement("script"),a.async="async",d.scriptCharset&&(a.charset=d.scriptCharset),a.src=d.url,a.onload=a.onreadystatechange=function(d,c){(c||!a.readyState||/loaded|complete/.test(a.readyState))&&(a.onload=a.onreadystatechange=null,e&&a.parentNode&&e.removeChild(a),a=b,c||f(200,"success"))},e.insertBefore(a,e.firstChild)},abort:function(){a&&a.onload(0,1)}}}}),F=!!d.ActiveXObject&&function(){for(var a in o)o[a](0,1)},bf=0;function K(){try{return new d.XMLHttpRequest}catch(a){}}function aU(){try{return new d.ActiveXObject("Microsoft.XMLHTTP")}catch(a){}}a.ajaxSettings.xhr=d.ActiveXObject?function(){return!this.isLocal&&K()||aU()}:K,function(b){a.extend(a.support,{ajax:!!b,cors:!!b&&"withCredentials"in b})}(a.ajaxSettings.xhr()),a.support.ajax&&a.ajaxTransport(function(c){if(!c.crossDomain||a.support.cors){var e;return{send:function(h,j){var f=c.xhr(),i,g;if(c.username?f.open(c.type,c.url,c.async,c.username,c.password):f.open(c.type,c.url,c.async),c.xhrFields)for(g in c.xhrFields)f[g]=c.xhrFields[g];c.mimeType&&f.overrideMimeType&&f.overrideMimeType(c.mimeType),!c.crossDomain&&!h["X-Requested-With"]&&(h["X-Requested-With"]="XMLHttpRequest");try{for(g in h)f.setRequestHeader(g,h[g])}catch(a){}f.send(c.hasContent&&c.data||null),e=function(n,k){var d,l,m,g,h;try{if(e&&(k||f.readyState===4))if(e=b,i&&(f.onreadystatechange=a.noop,F&&delete o[i]),k)f.readyState!==4&&f.abort();else{d=f.status,m=f.getAllResponseHeaders(),g={},h=f.responseXML,h&&h.documentElement&&(g.xml=h);try{g.text=f.responseText}catch(a){}try{l=f.statusText}catch(a){l=""}!d&&c.isLocal&&!c.crossDomain?d=g.text?200:404:d===1223&&(d=204)}}catch(a){k||j(-1,a)}g&&j(d,l,g,m)},!c.async||f.readyState===4?e():(i=++bf,F&&(o||(o={},a(d).unload(F)),o[i]=e),f.onreadystatechange=e)},abort:function(){e&&e(0,1)}}}}),I={},bm=/^(?:toggle|show|hide)$/,bn=/^([+\-]=)?([\d+.\-]+)([a-z%]*)$/i,s=[["height","marginTop","marginBottom","paddingTop","paddingBottom"],["width","marginLeft","marginRight","paddingLeft","paddingRight"],["opacity"]],a.fn.extend({show:function(e,g,h){var b,c,d,f;if(e||e===0)return this.animate(l("show",3),e,g,h);for(d=0,f=this.length;d<f;d++)b=this[d],b.style&&(c=b.style.display,!a._data(b,"olddisplay")&&c==="none"&&(c=b.style.display=""),(c===""&&a.css(b,"display")==="none"||!a.contains(b.ownerDocument.documentElement,b))&&a._data(b,"olddisplay",am(b.nodeName)));for(d=0;d<f;d++)b=this[d],b.style&&(c=b.style.display,(c===""||c==="none")&&(b.style.display=a._data(b,"olddisplay")||""));return this},hide:function(d,g,h){if(d||d===0)return this.animate(l("hide",3),d,g,h);for(var c,e,b=0,f=this.length;b<f;b++)c=this[b],c.style&&(e=a.css(c,"display"),e!=="none"&&!a._data(c,"olddisplay")&&a._data(c,"olddisplay",e));for(b=0;b<f;b++)this[b].style&&(this[b].style.display="none");return this},_toggle:a.fn.toggle,toggle:function(b,c,e){var d=typeof b=="boolean";return a.isFunction(b)&&a.isFunction(c)?this._toggle.apply(this,arguments):b==null||d?this.each(function(){var c=d?b:a(this).is(":hidden");a(this)[c?"show":"hide"]()}):this.animate(l("toggle",3),b,c,e),this},fadeTo:function(a,b,c,d){return this.filter(":hidden").css("opacity",0).show().end().animate({opacity:b},a,c,d)},animate:function(b,e,f,g){var c=a.speed(e,f,g);if(a.isEmptyObject(b))return this.each(c.complete,[!1]);b=a.extend({},b);function d(){c.queue===!1&&a._mark(this);var g=a.extend({},c),q=this.nodeType===1,o=q&&a(this).is(":hidden"),e,f,d,h,p,n,j,i,k,l,m;g.animatedProperties={};for(d in b)if(e=a.camelCase(d),d!==e&&(b[e]=b[d],delete b[d]),(p=a.cssHooks[e])&&"expand"in p){n=p.expand(b[e]),delete b[e];for(d in n)d in b||(b[d]=n[d])}for(e in b){if(f=b[e],a.isArray(f)?(g.animatedProperties[e]=f[1],f=b[e]=f[0]):g.animatedProperties[e]=g.specialEasing&&g.specialEasing[e]||g.easing||'swing',f==="hide"&&o||f==="show"&&!o)return g.complete.call(this);q&&(e==="height"||e==="width")&&(g.overflow=[this.style.overflow,this.style.overflowX,this.style.overflowY],a.css(this,"display")==="inline"&&a.css(this,"float")==="none"&&(!a.support.inlineBlockNeedsLayout||am(this.nodeName)==="inline"?this.style.display="inline-block":this.style.zoom=1))}g.overflow!=null&&(this.style.overflow="hidden");for(d in b)h=new a.fx(this,g,d),f=b[d],bm.test(f)?(m=a._data(this,"toggle"+d)||(f==="toggle"?o?"show":"hide":0),m?(a._data(this,"toggle"+d,m==="show"?"hide":"show"),h[m]()):h[f]()):(j=bn.exec(f.toString()),i=h.cur(),j?(k=parseFloat(j[2]),l=j[3]||(a.cssNumber[d]?"":"px"),l!=="px"&&(a.style(this,d,(k||1)+l),i=(k||1)/h.cur()*i,a.style(this,d,i+l)),j[1]&&(k=(j[1]==="-="?-1:1)*k+i),h.custom(i,k,l)):h.custom(i,f,""));return!0}return c.queue===!1?this.each(d):this.queue(c.queue,d)},stop:function(c,e,d){return typeof c!="string"&&(d=e,e=c,c=b),e&&c!==!1&&this.queue(c||"fx",[]),this.each(function(){var b,g=!1,f=a.timers,e=a._data(this);d||a._unmark(!0,this);function h(c,e,b){var f=e[b];a.removeData(c,b,!0),f.stop(d)}if(c==null){for(b in e)e[b]&&e[b].stop&&b.indexOf(".run")===b.length-4&&h(this,e,b)}else e[b=c+".run"]&&e[b].stop&&h(this,e,b);for(b=f.length;b--;)f[b].elem===this&&(c==null||f[b].queue===c)&&(d?f[b](!0):f[b].saveState(),g=!0,f.splice(b,1));d&&g||a.dequeue(this,c)})}});function al(){return setTimeout(bs,0),w=a.now()}function bs(){w=b}function l(c,d){var b={};return a.each(s.concat.apply([],s.slice(0,d)),function(){b[this]=c}),b}a.each({slideDown:l("show",1),slideUp:l("hide",1),slideToggle:l("toggle",1),fadeIn:{opacity:"show"},fadeOut:{opacity:"hide"},fadeToggle:{opacity:"toggle"}},function(b,c){a.fn[b]=function(a,b,d){return this.animate(c,a,b,d)}}),a.extend({speed:function(c,d,e){var b=c&&typeof c=="object"?a.extend({},c):{complete:e||!e&&d||a.isFunction(c)&&c,duration:c,easing:e&&d||d&&!a.isFunction(d)&&d};return b.duration=a.fx.off?0:typeof b.duration=="number"?b.duration:b.duration in a.fx.speeds?a.fx.speeds[b.duration]:a.fx.speeds._default,(b.queue==null||b.queue===!0)&&(b.queue="fx"),b.old=b.complete,b.complete=function(c){a.isFunction(b.old)&&b.old.call(this),b.queue?a.dequeue(this,b.queue):c!==!1&&a._unmark(this)},b},easing:{linear:function(a){return a},swing:function(a){return-Math.cos(a*Math.PI)/2+.5}},timers:[],fx:function(b,a,c){this.options=a,this.elem=b,this.prop=c,a.orig=a.orig||{}}}),a.fx.prototype={update:function(){this.options.step&&this.options.step.call(this.elem,this.now,this),(a.fx.step[this.prop]||a.fx.step._default)(this)},cur:function(){if(this.elem[this.prop]!=null&&(!this.elem.style||this.elem.style[this.prop]==null))return this.elem[this.prop];var c,b=a.css(this.elem,this.prop);return isNaN(c=parseFloat(b))?!b||b==="auto"?0:b:c},custom:function(f,g,h){var c=this,e=a.fx;this.startTime=w||al(),this.end=g,this.now=this.start=f,this.pos=this.state=0,this.unit=h||this.unit||(a.cssNumber[this.prop]?"":"px");function d(a){return c.step(a)}d.queue=this.options.queue,d.elem=this.elem,d.saveState=function(){a._data(c.elem,"fxshow"+c.prop)===b&&(c.options.hide?a._data(c.elem,"fxshow"+c.prop,c.start):c.options.show&&a._data(c.elem,"fxshow"+c.prop,c.end))},d()&&a.timers.push(d)&&!t&&(t=setInterval(e.tick,e.interval))},show:function(){var c=a._data(this.elem,"fxshow"+this.prop);this.options.orig[this.prop]=c||a.style(this.elem,this.prop),this.options.show=!0,c!==b?this.custom(this.cur(),c):this.custom(this.prop==="width"||this.prop==="height"?1:0,this.cur()),a(this.elem).show()},hide:function(){this.options.orig[this.prop]=a._data(this.elem,"fxshow"+this.prop)||a.style(this.elem,this.prop),this.options.hide=!0,this.custom(this.cur(),0)},step:function(i){var c,e,f,g=w||al(),h=!0,d=this.elem,b=this.options;if(i||g>=b.duration+this.startTime){this.now=this.end,this.pos=this.state=1,this.update(),b.animatedProperties[this.prop]=!0;for(c in b.animatedProperties)b.animatedProperties[c]!==!0&&(h=!1);if(h){if(b.overflow!=null&&!a.support.shrinkWrapBlocks&&a.each(["","X","Y"],function(a,c){d.style["overflow"+c]=b.overflow[a]}),b.hide&&a(d).hide(),b.hide||b.show)for(c in b.animatedProperties)a.style(d,c,b.orig[c]),a.removeData(d,"fxshow"+c,!0),a.removeData(d,"toggle"+c,!0);f=b.complete,f&&(b.complete=!1,f.call(d))}return!1}return b.duration==1/0?this.now=g:(e=g-this.startTime,this.state=e/b.duration,this.pos=a.easing[b.animatedProperties[this.prop]](this.state,e,0,1,b.duration),this.now=this.start+(this.end-this.start)*this.pos),this.update(),!0}},a.extend(a.fx,{tick:function(){for(var d,b=a.timers,c=0;c<b.length;c++)d=b[c],!d()&&b[c]===d&&b.splice(c--,1);b.length||a.fx.stop()},interval:13,stop:function(){clearInterval(t),t=null},speeds:{slow:600,fast:200,_default:400},step:{opacity:function(b){a.style(b.elem,"opacity",b.now)},_default:function(a){a.elem.style&&a.elem.style[a.prop]!=null?a.elem.style[a.prop]=a.now+a.unit:a.elem[a.prop]=a.now}}}),a.each(s.concat.apply([],s),function(c,b){b.indexOf("margin")&&(a.fx.step[b]=function(c){a.style(c.elem,b,Math.max(0,c.now)+c.unit)})}),a.expr&&a.expr.filters&&(a.expr.filters.animated=function(b){return a.grep(a.timers,function(a){return b===a.elem}).length});function am(b){if(!I[b]){var g=c.body,d=a("<"+b+">").appendTo(g),e=d.css("display");d.remove(),(e==="none"||e==="")&&(f||(f=c.createElement("iframe"),f.frameBorder=f.width=f.height=0),g.appendChild(f),(!k||!f.createElement)&&(k=(f.contentWindow||f.contentDocument).document,k.write((a.support.boxModel?"<!doctype html>":"")+"<html><body>"),k.close()),d=k.createElement(b),k.body.appendChild(d),e=a.css(d,"display"),g.removeChild(f)),I[b]=e}return I[b]}bw=/^t(?:able|d|h)$/i,au=/^(?:body|html)$/i,"getBoundingClientRect"in c.documentElement?C=function(e,f,c,b){try{b=e.getBoundingClientRect()}catch(a){}if(!b||!a.contains(c,e))return b?{top:b.top,left:b.left}:{top:0,left:0};var d=f.body,g=L(f),h=c.clientTop||d.clientTop||0,i=c.clientLeft||d.clientLeft||0,j=g.pageYOffset||a.support.boxModel&&c.scrollTop||d.scrollTop,k=g.pageXOffset||a.support.boxModel&&c.scrollLeft||d.scrollLeft,l=b.top+j-h,m=b.left+k-i;return{top:l,left:m}}:C=function(b,k,i){for(var c,j=b.offsetParent,l=b,g=k.body,h=k.defaultView,f=h?h.getComputedStyle(b,null):b.currentStyle,e=b.offsetTop,d=b.offsetLeft;(b=b.parentNode)&&b!==g&&b!==i;){if(a.support.fixedPosition&&f.position==="fixed")break;c=h?h.getComputedStyle(b,null):b.currentStyle,e-=b.scrollTop,d-=b.scrollLeft,b===j&&(e+=b.offsetTop,d+=b.offsetLeft,a.support.doesNotAddBorder&&!(a.support.doesAddBorderForTableAndCells&&bw.test(b.nodeName))&&(e+=parseFloat(c.borderTopWidth)||0,d+=parseFloat(c.borderLeftWidth)||0),l=j,j=b.offsetParent),a.support.subtractsBorderForOverflowNotVisible&&c.overflow!=="visible"&&(e+=parseFloat(c.borderTopWidth)||0,d+=parseFloat(c.borderLeftWidth)||0),f=c}return(f.position==="relative"||f.position==="static")&&(e+=g.offsetTop,d+=g.offsetLeft),a.support.fixedPosition&&f.position==="fixed"&&(e+=Math.max(i.scrollTop,g.scrollTop),d+=Math.max(i.scrollLeft,g.scrollLeft)),{top:e,left:d}},a.fn.offset=function(e){if(arguments.length)return e===b?this:this.each(function(b){a.offset.setOffset(this,e,b)});var c=this[0],d=c&&c.ownerDocument;return d?c===d.body?a.offset.bodyOffset(c):C(c,d,d.documentElement):null},a.offset={bodyOffset:function(b){var c=b.offsetTop,d=b.offsetLeft;return a.support.doesNotIncludeMarginInBodyOffset&&(c+=parseFloat(a.css(b,"marginTop"))||0,d+=parseFloat(a.css(b,"marginLeft"))||0),{top:c,left:d}},setOffset:function(c,b,n){var h=a.css(c,"position"),f,g,l,k,m,d,i,j,e;h==="static"&&(c.style.position="relative"),f=a(c),g=f.offset(),l=a.css(c,"top"),k=a.css(c,"left"),m=(h==="absolute"||h==="fixed")&&a.inArray("auto",[l,k])>-1,d={},i={},m?(i=f.position(),j=i.top,e=i.left):(j=parseFloat(l)||0,e=parseFloat(k)||0),a.isFunction(b)&&(b=b.call(c,n,g)),b.top!=null&&(d.top=b.top-g.top+j),b.left!=null&&(d.left=b.left-g.left+e),"using"in b?b.using.call(c,d):f.css(d)}},a.fn.extend({position:function(){if(!this[0])return null;var e=this[0],b=this.offsetParent(),c=this.offset(),d=au.test(b[0].nodeName)?{top:0,left:0}:b.offset();return c.top-=parseFloat(a.css(e,"marginTop"))||0,c.left-=parseFloat(a.css(e,"marginLeft"))||0,d.top+=parseFloat(a.css(b[0],"borderTopWidth"))||0,d.left+=parseFloat(a.css(b[0],"borderLeftWidth"))||0,{top:c.top-d.top,left:c.left-d.left}},offsetParent:function(){return this.map(function(){for(var b=this.offsetParent||c.body;b&&!au.test(b.nodeName)&&a.css(b,"position")==="static";)b=b.offsetParent;return b})}}),a.each({scrollLeft:"pageXOffset",scrollTop:"pageYOffset"},function(d,c){var e=/Y/.test(c);a.fn[d]=function(f){return a.access(this,function(h,f,g){var d=L(h);if(g===b)return d?c in d?d[c]:a.support.boxModel&&d.document.documentElement[f]||d.document.body[f]:h[f];d?d.scrollTo(e?a(d).scrollLeft():g,e?g:a(d).scrollTop()):h[f]=g},d,f,arguments.length,null)}});function L(b){return a.isWindow(b)?b:b.nodeType===9&&(b.defaultView||b.parentWindow)}a.each({Height:"height",Width:"width"},function(d,c){var e="client"+d,f="scroll"+d,g="offset"+d;a.fn["inner"+d]=function(){var b=this[0];return b?b.style?parseFloat(a.css(b,c,"padding")):this[c]():null},a.fn["outer"+d]=function(d){var b=this[0];return b?b.style?parseFloat(a.css(b,c,d?"margin":"border")):this[c]():null},a.fn[c]=function(d){return a.access(this,function(d,k,l){var c,h,i,j;if(a.isWindow(d))return c=d.document,h=c.documentElement[e],a.support.boxModel&&h||c.body&&c.body[e]||h;if(d.nodeType===9)return(c=d.documentElement,c[e]>=c[f])?c[e]:Math.max(d.body[f],c[f],d.body[g],c[g]);if(l===b)return i=a.css(d,k),j=parseFloat(i),a.isNumeric(j)?j:i;a(d).css(k,l)},c,d,arguments.length,null)}}),d.jQuery=d.$=a,typeof define=="function"&&define.amd&&define.amd.jQuery&&define("jquery",[],function(){return a})})(window),function(a){a.fn.hoverIntent=function(h,i){var b={sensitivity:7,interval:100,timeout:0},e,f,g,c,d,j,l,k;return b=a.extend(b,i?{over:h,out:i}:h),d=function(a){e=a.pageX,f=a.pageY},j=function(i,h){if(h.hoverIntent_t=clearTimeout(h.hoverIntent_t),Math.abs(g-e)+Math.abs(c-f)<b.sensitivity)return a(h).unbind("mousemove",d),h.hoverIntent_s=1,b.over.apply(h,[i]);g=e,c=f,h.hoverIntent_t=setTimeout(function(){j(i,h)},b.interval)},l=function(c,a){return a.hoverIntent_t=clearTimeout(a.hoverIntent_t),a.hoverIntent_s=0,b.out.apply(a,[c])},k=function(h){var f=jQuery.extend({},h),e=this;e.hoverIntent_t&&(e.hoverIntent_t=clearTimeout(e.hoverIntent_t)),h.type=="mouseenter"?(g=f.pageX,c=f.pageY,a(e).bind("mousemove",d),e.hoverIntent_s!=1&&(e.hoverIntent_t=setTimeout(function(){j(f,e)},b.interval))):(a(e).unbind("mousemove",d),e.hoverIntent_s==1&&(e.hoverIntent_t=setTimeout(function(){l(f,e)},b.timeout)))},this.bind('mouseenter',k).bind('mouseleave',k)}}(jQuery),jQuery.cookie=function(c,d,a){var f,b,l,j,k,h,i,e,g;if(typeof d!='undefined')a=a||{},d===null&&(d='',a.expires=-1),f='',a.expires&&(typeof a.expires=='number'||a.expires.toUTCString)&&(typeof a.expires=='number'?(b=new Date,b.setTime(b.getTime()+a.expires*24*60*60*1e3)):b=a.expires,f='; expires='+b.toUTCString()),l=a.path?'; path='+a.path:'',j=a.domain?'; domain='+a.domain:'',k=a.secure?'; secure':'',document.cookie=[c,'=',encodeURIComponent(d),f,l,j,k].join('');else{if(h=null,document.cookie&&document.cookie!=''){i=document.cookie.split(';');for(e=0;e<i.length;e++)if(g=jQuery.trim(i[e]),g.substring(0,c.length+1)==c+'='){h=decodeURIComponent(g.substring(c.length+1));break}}return h}},function(b,a){'use strict',typeof define=='function'&&define.amd?define(a):typeof exports!='undefined'?module.exports=a():b.simpleStorage=a()}(this,function(){'use strict';var n='0.1.3',a=!1,e=0,f=!1,c=null;function m(){window.localStorage.setItem('__simpleStorageInitTest','tmpval'),window.localStorage.removeItem('__simpleStorageInitTest'),j(),d(),l(),'addEventListener'in window&&window.addEventListener('pageshow',function(a){a.persisted&&h()},!1),f=!0}function l(){'addEventListener'in window?window.addEventListener('storage',h,!1):document.attachEvent('onstorage',h)}function h(){try{j()}catch(a){f=!1;return}d()}function j(){var b=localStorage.getItem('simpleStorage');try{a=JSON.parse(b)||{}}catch(b){a={}}e=k()}function b(){try{localStorage.setItem('simpleStorage',JSON.stringify(a)),e=k()}catch(a){return a}return!0}function k(){var a=localStorage.getItem('simpleStorage');return a?String(a).length:0}function d(){var j,e,l,g,f,h=1/0,k=0;if(clearTimeout(c),!a||!a.__simpleStorage_meta||!a.__simpleStorage_meta.TTL)return;j=+new Date,f=a.__simpleStorage_meta.TTL.keys||[],g=a.__simpleStorage_meta.TTL.expire||{};for(e=0,l=f.length;e<l;e++)if(g[f[e]]<=j)k++,delete a[f[e]],delete g[f[e]];else{g[f[e]]<h&&(h=g[f[e]]);break}h!=1/0&&(c=setTimeout(d,Math.min(h-j,2147483647))),k&&(f.splice(0,k),i(),b())}function g(e,g){var h=+new Date,b,f,j=!1;if(g=Number(g)||0,g!==0)if(a.hasOwnProperty(e)){if(a.__simpleStorage_meta||(a.__simpleStorage_meta={}),a.__simpleStorage_meta.TTL||(a.__simpleStorage_meta.TTL={expire:{},keys:[]}),a.__simpleStorage_meta.TTL.expire[e]=h+g,a.__simpleStorage_meta.TTL.expire.hasOwnProperty(e))for(b=0,f=a.__simpleStorage_meta.TTL.keys.length;b<f;b++)a.__simpleStorage_meta.TTL.keys[b]==e&&a.__simpleStorage_meta.TTL.keys.splice(b);for(b=0,f=a.__simpleStorage_meta.TTL.keys.length;b<f;b++)if(a.__simpleStorage_meta.TTL.expire[a.__simpleStorage_meta.TTL.keys[b]]>h+g){a.__simpleStorage_meta.TTL.keys.splice(b,0,e),j=!0;break}j||a.__simpleStorage_meta.TTL.keys.push(e)}else return!1;else if(a&&a.__simpleStorage_meta&&a.__simpleStorage_meta.TTL){if(a.__simpleStorage_meta.TTL.expire.hasOwnProperty(e)){delete a.__simpleStorage_meta.TTL.expire[e];for(b=0,f=a.__simpleStorage_meta.TTL.keys.length;b<f;b++)if(a.__simpleStorage_meta.TTL.keys[b]==e){a.__simpleStorage_meta.TTL.keys.splice(b,1);break}}i()}return clearTimeout(c),a&&a.__simpleStorage_meta&&a.__simpleStorage_meta.TTL&&a.__simpleStorage_meta.TTL.keys.length&&(c=setTimeout(d,Math.min(Math.max(a.__simpleStorage_meta.TTL.expire[a.__simpleStorage_meta.TTL.keys[0]]-h,0),2147483647))),!0}function i(){var b=!1,c=!1,d;if(!a||!a.__simpleStorage_meta)return b;a.__simpleStorage_meta.TTL&&!a.__simpleStorage_meta.TTL.keys.length&&(delete a.__simpleStorage_meta.TTL,b=!0);for(d in a.__simpleStorage_meta)if(a.__simpleStorage_meta.hasOwnProperty(d)){c=!0;break}return c||(delete a.__simpleStorage_meta,b=!0),b}try{m()}catch(a){}return{version:n,canUse:function(){return!!f},set:function(c,d,e){if(c=='__simpleStorage_meta')return!1;if(!a)return!1;if(typeof d=='undefined')return this.deleteKey(c);e=e||{};try{d=JSON.parse(JSON.stringify(d))}catch(a){return a}return a[c]=d,g(c,e.TTL||0),b()},get:function(b){if(!a)return!1;if(a.hasOwnProperty(b)&&b!='__simpleStorage_meta'){if(this.getTTL(b))return a[b]}},deleteKey:function(c){return!!a&&(c in a&&(delete a[c],g(c,0),b()))},setTTL:function(c,d){return!!a&&(g(c,d),b())},getTTL:function(b){var c;return!!a&&(!!a.hasOwnProperty(b)&&(a.__simpleStorage_meta&&a.__simpleStorage_meta.TTL&&a.__simpleStorage_meta.TTL.expire&&a.__simpleStorage_meta.TTL.expire.hasOwnProperty(b)?(c=Math.max(a.__simpleStorage_meta.TTL.expire[b]-+new Date||0,0),c||!1):1/0))},flush:function(){if(!a)return!1;a={};try{return localStorage.removeItem('simpleStorage'),!0}catch(a){return a}},index:function(){if(!a)return!1;var c=[],b;for(b in a)a.hasOwnProperty(b)&&b!='__simpleStorage_meta'&&c.push(b);return c},storageSize:function(){return e}}}),function(){var a,c,i,e,f,g,b,h,d=[].slice,j={}.hasOwnProperty,k=function(a,b){for(var c in b)j.call(b,c)&&(a[c]=b[c]);function d(){this.constructor=a}return d.prototype=b.prototype,a.prototype=new d,a.__super__=b.prototype,a};b=function(){},c=function(){function a(){}return a.prototype.addEventListener=a.prototype.on,a.prototype.on=function(a,b){return this._callbacks=this._callbacks||{},this._callbacks[a]||(this._callbacks[a]=[]),this._callbacks[a].push(b),this},a.prototype.emit=function(){var c,e,a,f,b,g;if(f=arguments[0],c=2<=arguments.length?d.call(arguments,1):[],this._callbacks=this._callbacks||{},a=this._callbacks[f],a)for(b=0,g=a.length;b<g;b++)e=a[b],e.apply(this,c);return this},a.prototype.removeListener=a.prototype.off,a.prototype.removeAllListeners=a.prototype.off,a.prototype.removeEventListener=a.prototype.off,a.prototype.off=function(d,g){var e,a,b,c,f;if(!this._callbacks||arguments.length===0)return this._callbacks={},this;if(a=this._callbacks[d],!a)return this;if(arguments.length===1)return delete this._callbacks[d],this;for(b=c=0,f=a.length;c<f;b=++c)if(e=a[b],e===g){a.splice(b,1);break}return this},a}(),a=function(i){var e,f;k(a,i),a.prototype.Emitter=c,a.prototype.events=["drop","dragstart","dragend","dragenter","dragover","dragleave","addedfile","addedfiles","removedfile","thumbnail","error","errormultiple","processing","processingmultiple","uploadprogress","totaluploadprogress","sending","sendingmultiple","success","successmultiple","canceled","canceledmultiple","complete","completemultiple","reset","maxfilesexceeded","maxfilesreached","queuecomplete"],a.prototype.defaultOptions={url:null,method:"post",withCredentials:!1,parallelUploads:2,uploadMultiple:!1,maxFilesize:256,paramName:"file",createImageThumbnails:!0,maxThumbnailFilesize:10,thumbnailWidth:120,thumbnailHeight:120,filesizeBase:1e3,maxFiles:null,params:{},clickable:!0,ignoreHiddenFiles:!0,acceptedFiles:null,acceptedMimeTypes:null,autoProcessQueue:!0,autoQueue:!0,addRemoveLinks:!1,previewsContainer:null,hiddenInputContainer:"body",capture:null,renameFilename:null,dictDefaultMessage:"Drop files here to upload",dictFallbackMessage:"Your browser does not support drag'n'drop file uploads.",dictFallbackText:"Please use the fallback form below to upload your files like in the olden days.",dictFileTooBig:"File is too big ({{filesize}}MiB). Max filesize: {{maxFilesize}}MiB.",dictInvalidFileType:"You can't upload files of this type.",dictResponseError:"Server responded with {{statusCode}} code.",dictCancelUpload:"Cancel upload",dictCancelUploadConfirmation:"Are you sure you want to cancel this upload?",dictRemoveFile:"Remove file",dictRemoveFileConfirmation:null,dictMaxFilesExceeded:"You can not upload any more files.",accept:function(b,a){return a()},init:function(){return b},forceFallback:!1,fallback:function(){var d,c,b,e,g,f;this.element.className=""+this.element.className+" dz-browser-not-supported",f=this.element.getElementsByTagName("div");for(e=0,g=f.length;e<g;e++)if(d=f[e],/(^| )dz-message($| )/.test(d.className)){c=d,d.className="dz-message";continue}return c||(c=a.createElement('<div class="dz-message"><span></span></div>'),this.element.appendChild(c)),b=c.getElementsByTagName("span")[0],b&&(b.textContent!=null?b.textContent=this.options.dictFallbackMessage:b.innerText!=null&&(b.innerText=this.options.dictFallbackMessage)),this.element.appendChild(this.getFallbackForm())},resize:function(b){var a,c,d;return a={srcX:0,srcY:0,srcWidth:b.width,srcHeight:b.height},c=b.width/b.height,a.optWidth=this.options.thumbnailWidth,a.optHeight=this.options.thumbnailHeight,a.optWidth==null&&a.optHeight==null?(a.optWidth=a.srcWidth,a.optHeight=a.srcHeight):a.optWidth==null?a.optWidth=c*a.optHeight:a.optHeight==null&&(a.optHeight=1/c*a.optWidth),d=a.optWidth/a.optHeight,b.height<a.optHeight||b.width<a.optWidth?(a.trgHeight=a.srcHeight,a.trgWidth=a.srcWidth):c>d?(a.srcHeight=b.height,a.srcWidth=a.srcHeight*d):(a.srcWidth=b.width,a.srcHeight=a.srcWidth/d),a.srcX=(b.width-a.srcWidth)/2,a.srcY=(b.height-a.srcHeight)/2,a},drop:function(a){return this.element.classList.remove("dz-drag-hover")},dragstart:b,dragend:function(a){return this.element.classList.remove("dz-drag-hover")},dragenter:function(a){return this.element.classList.add("dz-drag-hover")},dragover:function(a){return this.element.classList.add("dz-drag-hover")},dragleave:function(a){return this.element.classList.remove("dz-drag-hover")},paste:b,reset:function(){return this.element.classList.remove("dz-started")},addedfile:function(b){var f,n,m,d,e,c,o,l,k,i,h,g,j;if(this.element===this.previewsContainer&&this.element.classList.add("dz-started"),this.previewsContainer){b.previewElement=a.createElement(this.options.previewTemplate.trim()),b.previewTemplate=b.previewElement,this.previewsContainer.appendChild(b.previewElement),i=b.previewElement.querySelectorAll("[data-dz-name]");for(d=0,o=i.length;d<o;d++)f=i[d],f.textContent=this._renameFilename(b.name);h=b.previewElement.querySelectorAll("[data-dz-size]");for(e=0,l=h.length;e<l;e++)f=h[e],f.innerHTML=this.filesize(b.size);this.options.addRemoveLinks&&(b._removeLink=a.createElement('<a class="dz-remove" href="javascript:undefined;" data-dz-remove>'+this.options.dictRemoveFile+"</a>"),b.previewElement.appendChild(b._removeLink)),n=function(c){return function(d){return d.preventDefault(),d.stopPropagation(),b.status===a.UPLOADING?a.confirm(c.options.dictCancelUploadConfirmation,function(){return c.removeFile(b)}):c.options.dictRemoveFileConfirmation?a.confirm(c.options.dictRemoveFileConfirmation,function(){return c.removeFile(b)}):c.removeFile(b)}}(this),g=b.previewElement.querySelectorAll("[data-dz-remove]"),j=[];for(c=0,k=g.length;c<k;c++)m=g[c],j.push(m.addEventListener("click",n));return j}},removedfile:function(a){var b;return a.previewElement&&(b=a.previewElement)!=null&&b.parentNode.removeChild(a.previewElement),this._updateMaxFilesReachedClass()},thumbnail:function(a,f){var c,b,e,d;if(a.previewElement){a.previewElement.classList.remove("dz-file-preview"),d=a.previewElement.querySelectorAll("[data-dz-thumbnail]");for(b=0,e=d.length;b<e;b++)c=d[b],c.alt=a.name,c.src=f;return setTimeout(function(b){return function(){return a.previewElement.classList.add("dz-image-preview")}}(this),1)}},error:function(c,a){var f,b,g,d,e;if(c.previewElement){c.previewElement.classList.add("dz-error"),typeof a!="String"&&a.error&&(a=a.error),d=c.previewElement.querySelectorAll("[data-dz-errormessage]"),e=[];for(b=0,g=d.length;b<g;b++)f=d[b],e.push(f.textContent=a);return e}},errormultiple:b,processing:function(a){if(a.previewElement){if(a.previewElement.classList.add("dz-processing"),a._removeLink)return a._removeLink.textContent=this.options.dictCancelUpload}},processingmultiple:b,uploadprogress:function(f,g,h){var b,c,e,d,a;if(f.previewElement){d=f.previewElement.querySelectorAll("[data-dz-uploadprogress]"),a=[];for(c=0,e=d.length;c<e;c++)b=d[c],b.nodeName==='PROGRESS'?a.push(b.value=g):a.push(b.style.width=""+g+"%");return a}},totaluploadprogress:b,sending:b,sendingmultiple:b,success:function(a){if(a.previewElement)return a.previewElement.classList.add("dz-success")},successmultiple:b,canceled:function(a){return this.emit("error",a,"Upload canceled.")},canceledmultiple:b,complete:function(a){if(a._removeLink&&(a._removeLink.textContent=this.options.dictRemoveFile),a.previewElement)return a.previewElement.classList.add("dz-complete")},completemultiple:b,maxfilesexceeded:b,maxfilesreached:b,queuecomplete:b,addedfiles:b,previewTemplate:'<div class="dz-preview dz-file-preview">\n  <div class="dz-image"><img data-dz-thumbnail /></div>\n  <div class="dz-details">\n    <div class="dz-size"><span data-dz-size></span></div>\n    <div class="dz-filename"><span data-dz-name></span></div>\n  </div>\n  <div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div>\n  <div class="dz-error-message"><span data-dz-errormessage></span></div>\n  <div class="dz-success-mark">\n    <svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns">\n      <title>Check</title>\n      <defs></defs>\n      <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" sketch:type="MSPage">\n        <path d="M23.5,31.8431458 L17.5852419,25.9283877 C16.0248253,24.3679711 13.4910294,24.366835 11.9289322,25.9289322 C10.3700136,27.4878508 10.3665912,30.0234455 11.9283877,31.5852419 L20.4147581,40.0716123 C20.5133999,40.1702541 20.6159315,40.2626649 20.7218615,40.3488435 C22.2835669,41.8725651 24.794234,41.8626202 26.3461564,40.3106978 L43.3106978,23.3461564 C44.8771021,21.7797521 44.8758057,19.2483887 43.3137085,17.6862915 C41.7547899,16.1273729 39.2176035,16.1255422 37.6538436,17.6893022 L23.5,31.8431458 Z M27,53 C41.3594035,53 53,41.3594035 53,27 C53,12.6405965 41.3594035,1 27,1 C12.6405965,1 1,12.6405965 1,27 C1,41.3594035 12.6405965,53 27,53 Z" id="Oval-2" stroke-opacity="0.198794158" stroke="#747474" fill-opacity="0.816519475" fill="#FFFFFF" sketch:type="MSShapeGroup"></path>\n      </g>\n    </svg>\n  </div>\n  <div class="dz-error-mark">\n    <svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns">\n      <title>Error</title>\n      <defs></defs>\n      <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" sketch:type="MSPage">\n        <g id="Check-+-Oval-2" sketch:type="MSLayerGroup" stroke="#747474" stroke-opacity="0.198794158" fill="#FFFFFF" fill-opacity="0.816519475">\n          <path d="M32.6568542,29 L38.3106978,23.3461564 C39.8771021,21.7797521 39.8758057,19.2483887 38.3137085,17.6862915 C36.7547899,16.1273729 34.2176035,16.1255422 32.6538436,17.6893022 L27,23.3431458 L21.3461564,17.6893022 C19.7823965,16.1255422 17.2452101,16.1273729 15.6862915,17.6862915 C14.1241943,19.2483887 14.1228979,21.7797521 15.6893022,23.3461564 L21.3431458,29 L15.6893022,34.6538436 C14.1228979,36.2202479 14.1241943,38.7516113 15.6862915,40.3137085 C17.2452101,41.8726271 19.7823965,41.8744578 21.3461564,40.3106978 L27,34.6568542 L32.6538436,40.3106978 C34.2176035,41.8744578 36.7547899,41.8726271 38.3137085,40.3137085 C39.8758057,38.7516113 39.8771021,36.2202479 38.3106978,34.6538436 L32.6568542,29 Z M27,53 C41.3594035,53 53,41.3594035 53,27 C53,12.6405965 41.3594035,1 27,1 C12.6405965,1 1,12.6405965 1,27 C1,41.3594035 12.6405965,53 27,53 Z" id="Oval-2" sketch:type="MSShapeGroup"></path>\n        </g>\n      </g>\n    </svg>\n  </div>\n</div>'},e=function(){var b,c,e,f,g,a,h;f=arguments[0],e=2<=arguments.length?d.call(arguments,1):[];for(a=0,h=e.length;a<h;a++){c=e[a];for(b in c)g=c[b],f[b]=g}return f};function a(g,c){var d,b,f;if(this.element=g,this.version=a.version,this.defaultOptions.previewTemplate=this.defaultOptions.previewTemplate.replace(/\n*/g,""),this.clickableElements=[],this.listeners=[],this.files=[],typeof this.element=="string"&&(this.element=document.querySelector(this.element)),!(this.element&&this.element.nodeType!=null))throw new Error("Invalid dropzone element.");if(this.element.dropzone)throw new Error("Dropzone already attached.");if(a.instances.push(this),this.element.dropzone=this,d=(f=a.optionsForElement(this.element))!=null?f:{},this.options=e({},this.defaultOptions,d,c!=null?c:{}),this.options.forceFallback||!a.isBrowserSupported())return this.options.fallback.call(this);if(this.options.url==null&&(this.options.url=this.element.getAttribute("action")),!this.options.url)throw new Error("No URL provided.");if(this.options.acceptedFiles&&this.options.acceptedMimeTypes)throw new Error("You can't provide both 'acceptedFiles' and 'acceptedMimeTypes'. 'acceptedMimeTypes' is deprecated.");this.options.acceptedMimeTypes&&(this.options.acceptedFiles=this.options.acceptedMimeTypes,delete this.options.acceptedMimeTypes),this.options.method=this.options.method.toUpperCase(),(b=this.getExistingFallback())&&b.parentNode&&b.parentNode.removeChild(b),this.options.previewsContainer!==!1&&(this.options.previewsContainer?this.previewsContainer=a.getElement(this.options.previewsContainer,"previewsContainer"):this.previewsContainer=this.element),this.options.clickable&&(this.options.clickable===!0?this.clickableElements=[this.element]:this.clickableElements=a.getElements(this.options.clickable,"clickable")),this.init()}return a.prototype.getAcceptedFiles=function(){var b,a,e,c,d;c=this.files,d=[];for(a=0,e=c.length;a<e;a++)b=c[a],b.accepted&&d.push(b);return d},a.prototype.getRejectedFiles=function(){var b,a,e,c,d;c=this.files,d=[];for(a=0,e=c.length;a<e;a++)b=c[a],b.accepted||d.push(b);return d},a.prototype.getFilesWithStatus=function(f){var b,a,e,c,d;c=this.files,d=[];for(a=0,e=c.length;a<e;a++)b=c[a],b.status===f&&d.push(b);return d},a.prototype.getQueuedFiles=function(){return this.getFilesWithStatus(a.QUEUED)},a.prototype.getUploadingFiles=function(){return this.getFilesWithStatus(a.UPLOADING)},a.prototype.getAddedFiles=function(){return this.getFilesWithStatus(a.ADDED)},a.prototype.getActiveFiles=function(){var b,c,f,d,e;d=this.files,e=[];for(c=0,f=d.length;c<f;c++)b=d[c],(b.status===a.UPLOADING||b.status===a.QUEUED)&&e.push(b);return e},a.prototype.init=function(){var d,b,e,c,g,h,f;this.element.tagName==="form"&&this.element.setAttribute("enctype","multipart/form-data"),this.element.classList.contains("dropzone")&&!this.element.querySelector(".dz-message")&&this.element.appendChild(a.createElement('<div class="dz-default dz-message"><span>'+this.options.dictDefaultMessage+"</span></div>")),this.clickableElements.length&&(e=function(a){return function(){return a.hiddenFileInput&&a.hiddenFileInput.parentNode.removeChild(a.hiddenFileInput),a.hiddenFileInput=document.createElement("input"),a.hiddenFileInput.setAttribute("type","file"),(a.options.maxFiles==null||a.options.maxFiles>1)&&a.hiddenFileInput.setAttribute("multiple","multiple"),a.hiddenFileInput.className="dz-hidden-input",a.options.acceptedFiles!=null&&a.hiddenFileInput.setAttribute("accept",a.options.acceptedFiles),a.options.capture!=null&&a.hiddenFileInput.setAttribute("capture",a.options.capture),a.hiddenFileInput.style.visibility="hidden",a.hiddenFileInput.style.position="absolute",a.hiddenFileInput.style.top="0",a.hiddenFileInput.style.left="0",a.hiddenFileInput.style.height="0",a.hiddenFileInput.style.width="0",document.querySelector(a.options.hiddenInputContainer).appendChild(a.hiddenFileInput),a.hiddenFileInput.addEventListener("change",function(){var d,b,c,f;if(b=a.hiddenFileInput.files,b.length)for(c=0,f=b.length;c<f;c++)d=b[c],a.addFile(d);return a.emit("addedfiles",b),e()})}}(this),e()),this.URL=(h=window.URL)!=null?h:window.webkitURL,f=this.events;for(c=0,g=f.length;c<g;c++)d=f[c],this.on(d,this.options[d]);return this.on("uploadprogress",function(a){return function(){return a.updateTotalUploadProgress()}}(this)),this.on("removedfile",function(a){return function(){return a.updateTotalUploadProgress()}}(this)),this.on("canceled",function(a){return function(b){return a.emit("complete",b)}}(this)),this.on("complete",function(a){return function(b){if(a.getAddedFiles().length===0&&a.getUploadingFiles().length===0&&a.getQueuedFiles().length===0)return setTimeout(function(){return a.emit("queuecomplete")},0)}}(this)),b=function(a){return a.stopPropagation(),a.preventDefault?a.preventDefault():a.returnValue=!1},this.listeners=[{element:this.element,events:{dragstart:function(a){return function(b){return a.emit("dragstart",b)}}(this),dragenter:function(a){return function(c){return b(c),a.emit("dragenter",c)}}(this),dragover:function(a){return function(c){var d;try{d=c.dataTransfer.effectAllowed}catch(a){}return c.dataTransfer.dropEffect='move'===d||'linkMove'===d?'move':'copy',b(c),a.emit("dragover",c)}}(this),dragleave:function(a){return function(b){return a.emit("dragleave",b)}}(this),drop:function(a){return function(c){return b(c),a.drop(c)}}(this),dragend:function(a){return function(b){return a.emit("dragend",b)}}(this)}}],this.clickableElements.forEach(function(b){return function(c){return b.listeners.push({element:c,events:{click:function(d){return(c!==b.element||d.target===b.element||a.elementInside(d.target,b.element.querySelector(".dz-message")))&&b.hiddenFileInput.click(),!0}}})}}(this)),this.enable(),this.options.init.call(this)},a.prototype.destroy=function(){var b;return this.disable(),this.removeAllFiles(!0),((b=this.hiddenFileInput)!=null?b.parentNode:void 0)&&(this.hiddenFileInput.parentNode.removeChild(this.hiddenFileInput),this.hiddenFileInput=null),delete this.element.dropzone,a.instances.splice(a.instances.indexOf(this),1)},a.prototype.updateTotalUploadProgress=function(){var g,d,a,b,e,c,h,f;if(b=0,a=0,g=this.getActiveFiles(),g.length){f=this.getActiveFiles();for(c=0,h=f.length;c<h;c++)d=f[c],b+=d.upload.bytesSent,a+=d.upload.total;e=100*b/a}else e=100;return this.emit("totaluploadprogress",e,a,b)},a.prototype._getParamName=function(a){return typeof this.options.paramName=="function"?this.options.paramName(a):""+this.options.paramName+(this.options.uploadMultiple?"["+a+"]":"")},a.prototype._renameFilename=function(a){return typeof this.options.renameFilename!="function"?a:this.options.renameFilename(a)},a.prototype.getFallbackForm=function(){var e,d,b,c;return(e=this.getExistingFallback())?e:(b='<div class="dz-fallback">',this.options.dictFallbackText&&(b+="<p>"+this.options.dictFallbackText+"</p>"),b+='<input type="file" name="'+this._getParamName(0)+'" '+(this.options.uploadMultiple?'multiple="multiple"':void 0)+' /><input type="submit" value="Upload!"></div>',d=a.createElement(b),this.element.tagName!=="FORM"?(c=a.createElement('<form action="'+this.options.url+'" enctype="multipart/form-data" method="'+this.options.method+'"></form>'),c.appendChild(d)):(this.element.setAttribute("enctype","multipart/form-data"),this.element.setAttribute("method",this.options.method)),c!=null?c:d)},a.prototype.getExistingFallback=function(){var c,d,e,a,f,b;d=function(c){var b,a,d;for(a=0,d=c.length;a<d;a++)if(b=c[a],/(^| )fallback($| )/.test(b.className))return b},b=["div","form"];for(a=0,f=b.length;a<f;a++)if(e=b[a],c=d(this.element.getElementsByTagName(e)))return c},a.prototype.setupEventListeners=function(){var b,c,f,a,g,d,e;d=this.listeners,e=[];for(a=0,g=d.length;a<g;a++)b=d[a],e.push(function(){var a,d;a=b.events,d=[];for(c in a)f=a[c],d.push(b.element.addEventListener(c,f,!1));return d}());return e},a.prototype.removeEventListeners=function(){var b,c,f,a,g,d,e;d=this.listeners,e=[];for(a=0,g=d.length;a<g;a++)b=d[a],e.push(function(){var a,d;a=b.events,d=[];for(c in a)f=a[c],d.push(b.element.removeEventListener(c,f,!1));return d}());return e},a.prototype.disable=function(){var d,a,e,b,c;this.clickableElements.forEach(function(a){return a.classList.remove("dz-clickable")}),this.removeEventListeners(),b=this.files,c=[];for(a=0,e=b.length;a<e;a++)d=b[a],c.push(this.cancelUpload(d));return c},a.prototype.enable=function(){return this.clickableElements.forEach(function(a){return a.classList.add("dz-clickable")}),this.setupEventListeners()},a.prototype.filesize=function(c){var h,a,b,e,g,f,d,i;if(b=0,e="b",c>0){f=['TB','GB','MB','KB','b'];for(a=d=0,i=f.length;d<i;a=++d)if(g=f[a],h=Math.pow(this.options.filesizeBase,4-a)/10,c>=h){b=c/Math.pow(this.options.filesizeBase,4-a),e=g;break}b=Math.round(10*b)/10}return"<strong>"+b+"</strong> "+e},a.prototype._updateMaxFilesReachedClass=function(){return this.options.maxFiles!=null&&this.getAcceptedFiles().length>=this.options.maxFiles?(this.getAcceptedFiles().length===this.options.maxFiles&&this.emit('maxfilesreached',this.files),this.element.classList.add("dz-max-files-reached")):this.element.classList.remove("dz-max-files-reached")},a.prototype.drop=function(b){var c,a;if(!b.dataTransfer)return;this.emit("drop",b),c=b.dataTransfer.files,this.emit("addedfiles",c),c.length&&(a=b.dataTransfer.items,a&&a.length&&a[0].webkitGetAsEntry!=null?this._addFilesFromItems(a):this.handleFiles(c))},a.prototype.paste=function(a){var b,c;if((a!=null?(c=a.clipboardData)!=null?c.items:void 0:void 0)==null)return;if(this.emit("paste",a),b=a.clipboardData.items,b.length)return this._addFilesFromItems(b)},a.prototype.handleFiles=function(c){var d,a,e,b;b=[];for(a=0,e=c.length;a<e;a++)d=c[a],b.push(this.addFile(d));return b},a.prototype._addFilesFromItems=function(e){var c,a,d,f,b;b=[];for(d=0,f=e.length;d<f;d++)a=e[d],a.webkitGetAsEntry!=null&&(c=a.webkitGetAsEntry())?c.isFile?b.push(this.addFile(a.getAsFile())):c.isDirectory?b.push(this._addFilesFromDirectory(c,c.name)):b.push(void 0):a.getAsFile!=null?a.kind==null||a.kind==="file"?b.push(this.addFile(a.getAsFile())):b.push(void 0):b.push(void 0);return b},a.prototype._addFilesFromDirectory=function(e,b){var c,d,a;return c=e.createReader(),d=function(a){return typeof console!="undefined"&&console!==null?typeof console.log=="function"?console.log(a):void 0:void 0},a=function(e){return function(){return c.readEntries(function(f){var c,d,g;if(f.length>0){for(d=0,g=f.length;d<g;d++)c=f[d],c.isFile?c.file(function(a){if(e.options.ignoreHiddenFiles&&a.name.substring(0,1)==='.')return;return a.fullPath=""+b+"/"+a.name,e.addFile(a)}):c.isDirectory&&e._addFilesFromDirectory(c,""+b+"/"+c.name);a()}return null},d)}}(this),a()},a.prototype.accept=function(b,c){return b.size>this.options.maxFilesize*1024*1024?c(this.options.dictFileTooBig.replace("{{filesize}}",Math.round(b.size/1024/10.24)/100).replace("{{maxFilesize}}",this.options.maxFilesize)):a.isValidFile(b,this.options.acceptedFiles)?this.options.maxFiles!=null&&this.getAcceptedFiles().length>=this.options.maxFiles?(c(this.options.dictMaxFilesExceeded.replace("{{maxFiles}}",this.options.maxFiles)),this.emit("maxfilesexceeded",b)):this.options.accept.call(this,b,c):c(this.options.dictInvalidFileType)},a.prototype.addFile=function(b){return b.upload={progress:0,total:b.size,bytesSent:0},this.files.push(b),b.status=a.ADDED,this.emit("addedfile",b),this._enqueueThumbnail(b),this.accept(b,function(a){return function(c){return c?(b.accepted=!1,a._errorProcessing([b],c)):(b.accepted=!0,a.options.autoQueue&&a.enqueueFile(b)),a._updateMaxFilesReachedClass()}}(this))},a.prototype.enqueueFiles=function(b){var c,a,d;for(a=0,d=b.length;a<d;a++)c=b[a],this.enqueueFile(c);return null},a.prototype.enqueueFile=function(b){if(b.status===a.ADDED&&b.accepted===!0){if(b.status=a.QUEUED,this.options.autoProcessQueue)return setTimeout(function(a){return function(){return a.processQueue()}}(this),0)}else throw new Error("This file can't be queued because it has already been processed or was rejected.")},a.prototype._thumbnailQueue=[],a.prototype._processingThumbnail=!1,a.prototype._enqueueThumbnail=function(a){if(this.options.createImageThumbnails&&a.type.match(/image.*/)&&a.size<=this.options.maxThumbnailFilesize*1024*1024)return this._thumbnailQueue.push(a),setTimeout(function(a){return function(){return a._processThumbnailQueue()}}(this),0)},a.prototype._processThumbnailQueue=function(){if(this._processingThumbnail||this._thumbnailQueue.length===0)return;return this._processingThumbnail=!0,this.createThumbnail(this._thumbnailQueue.shift(),function(a){return function(){return a._processingThumbnail=!1,a._processThumbnailQueue()}}(this))},a.prototype.removeFile=function(b){if(b.status===a.UPLOADING&&this.cancelUpload(b),this.files=h(this.files,b),this.emit("removedfile",b),this.files.length===0)return this.emit("reset")},a.prototype.removeAllFiles=function(c){var d,b,f,e;c==null&&(c=!1),e=this.files.slice();for(b=0,f=e.length;b<f;b++)d=e[b],(d.status!==a.UPLOADING||c)&&this.removeFile(d);return null},a.prototype.createThumbnail=function(b,c){var a;return a=new FileReader,a.onload=function(d){return function(){if(b.type==="image/svg+xml"){d.emit("thumbnail",b,a.result),c!=null&&c();return}return d.createThumbnailFromUrl(b,a.result,c)}}(this),a.readAsDataURL(b)},a.prototype.createThumbnailFromUrl=function(b,e,c,d){var a;return a=document.createElement("img"),d&&(a.crossOrigin=d),a.onload=function(d){return function(){var f,h,e,i,j,k,l,m;if(b.width=a.width,b.height=a.height,e=d.options.resize.call(d,b),e.trgWidth==null&&(e.trgWidth=e.optWidth),e.trgHeight==null&&(e.trgHeight=e.optHeight),f=document.createElement("canvas"),h=f.getContext("2d"),f.width=e.trgWidth,f.height=e.trgHeight,g(h,a,(j=e.srcX)!=null?j:0,(k=e.srcY)!=null?k:0,e.srcWidth,e.srcHeight,(l=e.trgX)!=null?l:0,(m=e.trgY)!=null?m:0,e.trgWidth,e.trgHeight),i=f.toDataURL("image/png"),d.emit("thumbnail",b,i),c!=null)return c()}}(this),c!=null&&(a.onerror=c),a.src=e},a.prototype.processQueue=function(){var d,b,c,a;if(b=this.options.parallelUploads,c=this.getUploadingFiles().length,d=c,c>=b)return;if(a=this.getQueuedFiles(),!(a.length>0))return;if(this.options.uploadMultiple)return this.processFiles(a.slice(0,b-c));while(d<b){if(!a.length)return;this.processFile(a.shift()),d++}},a.prototype.processFile=function(a){return this.processFiles([a])},a.prototype.processFiles=function(b){var c,d,e;for(d=0,e=b.length;d<e;d++)c=b[d],c.processing=!0,c.status=a.UPLOADING,this.emit("processing",c);return this.options.uploadMultiple&&this.emit("processingmultiple",b),this.uploadFiles(b)},a.prototype._getFilesWithXhr=function(b){var a,c;return c=function(){var c,f,d,e;d=this.files,e=[];for(c=0,f=d.length;c<f;c++)a=d[c],a.xhr===b&&e.push(a);return e}.call(this)},a.prototype.cancelUpload=function(b){var d,c,e,f,g,h,i;if(b.status===a.UPLOADING){c=this._getFilesWithXhr(b.xhr);for(e=0,g=c.length;e<g;e++)d=c[e],d.status=a.CANCELED;b.xhr.abort();for(f=0,h=c.length;f<h;f++)d=c[f],this.emit("canceled",d);this.options.uploadMultiple&&this.emit("canceledmultiple",c)}else((i=b.status)===a.ADDED||i===a.QUEUED)&&(b.status=a.CANCELED,this.emit("canceled",b),this.options.uploadMultiple&&this.emit("canceledmultiple",[b]));if(this.options.autoProcessQueue)return this.processQueue()},f=function(){var b,a;return(a=arguments[0],b=2<=arguments.length?d.call(arguments,1):[],typeof a=='function')?a.apply(this,b):a},a.prototype.uploadFile=function(a){return this.uploadFiles([a])},a.prototype.uploadFiles=function(b){var d,h,A,w,u,m,k,g,B,z,y,J,t,I,i,r,L,H,c,p,o,n,q,F,C,D,E,j,G,v,s,x,K,l;c=new XMLHttpRequest;for(p=0,F=b.length;p<F;p++)d=b[p],d.xhr=c;J=f(this.options.method,b),L=f(this.options.url,b),c.open(J,L,!0),c.withCredentials=!!this.options.withCredentials,i=null,A=function(a){return function(){var e,g,f;f=[];for(e=0,g=b.length;e<g;e++)d=b[e],f.push(a._errorProcessing(b,i||a.options.dictResponseError.replace("{{statusCode}}",c.status),c));return f}}(this),r=function(a){return function(c){var i,e,f,g,h,k,l,m,j;if(c!=null){e=100*c.loaded/c.total;for(f=0,k=b.length;f<k;f++)d=b[f],d.upload={progress:e,total:c.total,bytesSent:c.loaded}}else{i=!0,e=100;for(g=0,l=b.length;g<l;g++)d=b[g],d.upload.progress===100&&d.upload.bytesSent===d.upload.total||(i=!1),d.upload.progress=e,d.upload.bytesSent=d.upload.total;if(i)return}j=[];for(h=0,m=b.length;h<m;h++)d=b[h],j.push(a.emit("uploadprogress",d,e,d.upload.bytesSent));return j}}(this),c.onload=function(d){return function(e){var f;if(b[0].status===a.CANCELED)return;if(c.readyState!==4)return;if(i=c.responseText,c.getResponseHeader("content-type")&&~c.getResponseHeader("content-type").indexOf("application/json"))try{i=JSON.parse(i)}catch(a){e=a,i="Invalid JSON response from server."}return r(),200<=(f=c.status)&&f<300?d._finished(b,i,e):A()}}(this),c.onerror=function(c){return function(){if(b[0].status===a.CANCELED)return;return A()}}(this),I=(G=c.upload)!=null?G:c,I.onprogress=r,m={Accept:"application/json","Cache-Control":"no-cache","X-Requested-With":"XMLHttpRequest"},this.options.headers&&e(m,this.options.headers);for(w in m)u=m[w],u&&c.setRequestHeader(w,u);if(h=new FormData,this.options.params){v=this.options.params;for(y in v)H=v[y],h.append(y,H)}for(o=0,C=b.length;o<C;o++)d=b[o],this.emit("sending",d,c,h);if(this.options.uploadMultiple&&this.emit("sendingmultiple",b,c,h),this.element.tagName==="FORM"){s=this.element.querySelectorAll("input, textarea, select, button");for(n=0,D=s.length;n<D;n++)if(g=s[n],B=g.getAttribute("name"),z=g.getAttribute("type"),g.tagName==="SELECT"&&g.hasAttribute("multiple")){{x=g.options;for(q=0,E=x.length;q<E;q++)t=x[q],t.selected&&h.append(B,t.value)}}else(!z||(K=z.toLowerCase())!=="checkbox"&&K!=="radio"||g.checked)&&h.append(B,g.value)}for(k=j=0,l=b.length-1;0<=l?j<=l:j>=l;k=0<=l?++j:--j)h.append(this._getParamName(k),b[k],this._renameFilename(b[k].name));return this.submitRequest(c,h,b)},a.prototype.submitRequest=function(a,b,c){return a.send(b)},a.prototype._finished=function(b,e,f){var c,d,g;for(d=0,g=b.length;d<g;d++)c=b[d],c.status=a.SUCCESS,this.emit("success",c,e,f),this.emit("complete",c);if(this.options.uploadMultiple&&(this.emit("successmultiple",b,e,f),this.emit("completemultiple",b)),this.options.autoProcessQueue)return this.processQueue()},a.prototype._errorProcessing=function(b,e,f){var c,d,g;for(d=0,g=b.length;d<g;d++)c=b[d],c.status=a.ERROR,this.emit("error",c,e,f),this.emit("complete",c);if(this.options.uploadMultiple&&(this.emit("errormultiple",b,e,f),this.emit("completemultiple",b)),this.options.autoProcessQueue)return this.processQueue()},a}(c),a.version="4.3.0",a.options={},a.optionsForElement=function(b){return b.getAttribute("id")?a.options[i(b.getAttribute("id"))]:void 0},a.instances=[],a.forElement=function(a){if(typeof a=="string"&&(a=document.querySelector(a)),(a!=null?a.dropzone:void 0)==null)throw new Error("No Dropzone found for given element. This is probably because you're trying to access it before Dropzone had the time to initialize. Use the `init` option to setup any additional observers on your Dropzone.");return a.dropzone},a.autoDiscover=!0,a.discover=function(){var e,f,b,c,g,d;document.querySelectorAll?b=document.querySelectorAll(".dropzone"):(b=[],e=function(e){var d,a,f,c;c=[];for(a=0,f=e.length;a<f;a++)d=e[a],/(^| )dropzone($| )/.test(d.className)?c.push(b.push(d)):c.push(void 0);return c},e(document.getElementsByTagName("div")),e(document.getElementsByTagName("form"))),d=[];for(c=0,g=b.length;c<g;c++)f=b[c],a.optionsForElement(f)!==!1?d.push(new a(f)):d.push(void 0);return d},a.blacklistedBrowsers=[/opera.*Macintosh.*version\/12/i],a.isBrowserSupported=function(){var b,e,c,f,d;if(b=!0,window.File&&window.FileReader&&window.FileList&&window.Blob&&window.FormData&&document.querySelector)if("classList"in document.createElement("a")){{d=a.blacklistedBrowsers;for(c=0,f=d.length;c<f;c++)if(e=d[c],e.test(navigator.userAgent)){b=!1;continue}}}else b=!1;else b=!1;return b},h=function(d,f){var b,a,e,c;c=[];for(a=0,e=d.length;a<e;a++)b=d[a],b!==f&&c.push(b);return c},i=function(a){return a.replace(/[\-_](\w)/g,function(a){return a.charAt(1).toUpperCase()})},a.createElement=function(b){var a;return a=document.createElement("div"),a.innerHTML=b,a.childNodes[0]},a.elementInside=function(a,b){if(a===b)return!0;while(a=a.parentNode)if(a===b)return!0;return!1},a.getElement=function(a,c){var b;if(typeof a=="string"?b=document.querySelector(a):a.nodeType!=null&&(b=a),b==null)throw new Error("Invalid `"+c+"` option provided. Please provide a CSS selector or a plain HTML element.");return b},a.getElements=function(b,g){var j,c,a,d,e,i,h,f;if(b instanceof Array){a=[];try{for(d=0,i=b.length;d<i;d++)c=b[d],a.push(this.getElement(c,g))}catch(b){j=b,a=null}}else if(typeof b=="string"){a=[],f=document.querySelectorAll(b);for(e=0,h=f.length;e<h;e++)c=f[e],a.push(c)}else b.nodeType!=null&&(a=[b]);if(!(a!=null&&a.length))throw new Error("Invalid `"+g+"` option provided. Please provide a CSS selector, a plain HTML element or a list of those.");return a},a.confirm=function(b,c,a){if(window.confirm(b))return c();if(a!=null)return a()},a.isValidFile=function(d,b){var f,e,a,c,g;if(!b)return!0;b=b.split(","),e=d.type,f=e.replace(/\/.*$/,"");for(c=0,g=b.length;c<g;c++)if(a=b[c],a=a.trim(),a.charAt(0)==="."){if(d.name.toLowerCase().indexOf(a.toLowerCase(),d.name.length-a.length)!==-1)return!0}else if(/\/\*$/.test(a)){if(f===a.replace(/\/.*$/,""))return!0}else if(e===a)return!0;return!1},typeof jQuery!="undefined"&&jQuery!==null&&(jQuery.fn.dropzone=function(b){return this.each(function(){return new a(this,b)})}),typeof module!="undefined"&&module!==null?module.exports=a:window.Dropzone=a,a.ADDED="added",a.QUEUED="queued",a.ACCEPTED=a.QUEUED,a.UPLOADING="uploading",a.PROCESSING=a.UPLOADING,a.CANCELED="canceled",a.ERROR="error",a.SUCCESS="success",f=function(g){var i,d,e,j,f,b,k,a,h,c;for(k=g.naturalWidth,b=g.naturalHeight,d=document.createElement("canvas"),d.width=1,d.height=b,e=d.getContext("2d"),e.drawImage(g,0,0),j=e.getImageData(0,0,1,b).data,c=0,f=b,a=b;a>c;)i=j[(a-1)*4+3],i===0?f=a:c=a,a=f+c>>1;return h=a/b,h===0?1:h},g=function(c,a,d,e,l,g,h,i,j,k){var b;return b=f(a),c.drawImage(a,d,e,l,g,h,i,j,k/b)},e=function(c,k){var e,a,f,b,g,d,j,h,i;if(f=!1,i=!0,a=c.document,h=a.documentElement,e=a.addEventListener?"addEventListener":"attachEvent",j=a.addEventListener?"removeEventListener":"detachEvent",d=a.addEventListener?"":"on",b=function(e){if(e.type==="readystatechange"&&a.readyState!=="complete")return;if((e.type==="load"?c:a)[j](d+e.type,b,!1),!f&&(f=!0))return k.call(c,e.type||e)},g=function(){var a;try{h.doScroll("left")}catch(b){a=b,setTimeout(g,50);return}return b("poll")},a.readyState!=="complete"){if(a.createEventObject&&h.doScroll){try{i=!c.frameElement}catch(a){}i&&g()}return a[e](d+"DOMContentLoaded",b,!1),a[e](d+"readystatechange",b,!1),c[e](d+"load",b,!1)}},a._autoDiscoverFunction=function(){if(a.autoDiscover)return a.discover()},e(window,a._autoDiscoverFunction)}.call(this),function(a){a.idleTimer=function(b,c){var f,d,e,g,i,h,j,k,l,m;if(typeof b=="object"?(f=b,b=null):typeof b=="number"&&(f={timeout:b},b=null),c=c||document,f=a.extend({idle:!1,timeout:3e4,events:"mousemove keydown wheel DOMMouseScroll mousewheel mousedown touchstart touchmove MSPointerDown MSPointerMove"},f),d=a(c),e=d.data("idleTimerObj")||{},g=function(d){var b=a.data(c,"idleTimerObj")||{},e;b.idle=!b.idle,b.olddate=+new Date,e=a.Event((b.idle?"idle":"active")+".idleTimer"),a(c).trigger(e,[c,a.extend({},b),d])},i=function(d){var b=a.data(c,"idleTimerObj")||{},e;if(b.remaining!=null)return;if(d.type==="mousemove"){if(d.pageX===b.pageX&&d.pageY===b.pageY)return;if(typeof d.pageX=="undefined"&&typeof d.pageY=="undefined")return;if(e=+new Date-b.olddate,e<200)return}clearTimeout(b.tId),b.idle&&g(d),b.lastActive=+new Date,b.pageX=d.pageX,b.pageY=d.pageY,b.tId=setTimeout(g,b.timeout)},h=function(){var b=a.data(c,"idleTimerObj")||{};b.idle=b.idleBackup,b.olddate=+new Date,b.lastActive=b.olddate,b.remaining=null,clearTimeout(b.tId),b.idle||(b.tId=setTimeout(g,b.timeout))},j=function(){var b=a.data(c,"idleTimerObj")||{};if(b.remaining!=null)return;b.remaining=b.timeout-(+new Date-b.olddate),clearTimeout(b.tId)},k=function(){var b=a.data(c,"idleTimerObj")||{};if(b.remaining==null)return;b.idle||(b.tId=setTimeout(g,b.remaining)),b.remaining=null},l=function(){var b=a.data(c,"idleTimerObj")||{};clearTimeout(b.tId),d.removeData("idleTimerObj"),d.off("._idleTimer")},m=function(){var b=a.data(c,"idleTimerObj")||{},d;return b.idle?0:b.remaining!=null?b.remaining:(d=b.timeout-(+new Date-b.lastActive),d<0&&(d=0),d)},b===null&&typeof e.idle!="undefined")return h(),d;if(b===null);else if(b!==null&&typeof e.idle=="undefined")return!1;else if(b==="destroy")return l(),d;else if(b==="pause")return j(),d;else if(b==="resume")return k(),d;else if(b==="reset")return h(),d;else if(b==="getRemainingTime")return m();else if(b==="getElapsedTime")return+new Date-e.olddate;else if(b==="getLastActiveTime")return e.lastActive;else if(b==="isIdle")return e.idle;return d.on(a.trim((f.events+" ").split(" ").join("._idleTimer ")),function(a){i(a)}),e=a.extend({},{olddate:+new Date,lastActive:+new Date,idle:f.idle,idleBackup:f.idle,timeout:f.timeout,remaining:null,tId:null,pageX:null,pageY:null}),e.idle||(e.tId=setTimeout(g,e.timeout)),a.data(c,"idleTimerObj",e),d},a.fn.idleTimer=function(b){return this[0]?a.idleTimer(b,this[0]):this}}(jQuery),function(b,a){typeof define=='function'&&define.amd?define(['jquery'],a):a(b.jQuery)}(this,function(a){var i={seconds:0,editable:!1,restart:!1,duration:null,callback:function(){alert('Time up!')},startTimer:function(){},pauseTimer:function(){},resumeTimer:function(){},resetTimer:function(){},removeTimer:function(){},repeat:!1,countdown:!1,format:null,updateFrequency:1e3,state:'running'},c='html',e='stopped',f='running',u='paused',g,b;function m(b){var c=b.element;a(c).data('intr',setInterval(r.bind(b),b.options.updateFrequency)),a(c).data('isTimerRunning',!0)}function h(b){clearInterval(a(b.element).data('intr')),a(b.element).data('isTimerRunning',!1)}function r(){a(this.element).data('totalSeconds',j()-a(this.element).data('startTime')),d(this),a(this.element).data('duration')&&a(this.element).data('totalSeconds')%a(this.element).data('duration')===0&&(this.options.repeat||(a(this.element).data('duration',null),this.options.duration=null),this.options.countdown&&(h(this),this.options.countdown=!1,a(this.element).data('state',e)),this.options.callback())}function d(d){var b=d.element,e=a(b).data('totalSeconds');d.options.countdown&&a(b).data('duration')>0&&(e=a(b).data('duration')-a(b).data('totalSeconds')),a(b)[c](o(e,d)),a(b).data('seconds',e)}function q(d){var b=d.element;a(b).on('focus',function(){k(d)}),a(b).on('blur',function(){var e=a(b)[c](),f;e.indexOf('sec')>0?a(b).data('totalSeconds',Number(e.replace(/\ssec/g,''))):e.indexOf('min')>0?(e=e.replace(/\smin/g,''),f=e.split(':'),a(b).data('totalSeconds',Number(f[0]*60)+Number(f[1]))):e.match(/\d{1,2}:\d{2}:\d{2}/)&&(f=e.split(':'),a(b).data('totalSeconds',Number(f[0]*3600)+Number(f[1]*60)+Number(f[2]))),l(d)})}function j(){return Math.round((new Date).getTime()/1e3)}function v(a){var d=0,b=Math.floor(a/60),c;return a>=3600&&(d=Math.floor(a/3600)),a>=3600&&(b=Math.floor(a%3600/60)),b<10&&d>0&&(b='0'+b),c=a%60,c<10&&(b>0||d>0)&&(c='0'+c),{hours:d,minutes:b,seconds:c}}function o(d,c){var b='',a=v(d),e;return c.options.format?(e=[{identifier:'%h',value:a.hours,pad:!1},{identifier:'%m',value:a.minutes,pad:!1},{identifier:'%s',value:a.seconds,pad:!1},{identifier:'%H',value:parseInt(a.hours),pad:!0},{identifier:'%M',value:parseInt(a.minutes),pad:!0},{identifier:'%S',value:parseInt(a.seconds),pad:!0}],b=c.options.format,e.forEach(function(a){b=b.replace(new RegExp(a.identifier.replace(/([.*+?^=!:${}()|\[\]\/\\])/g,'\\$1'),'g'),a.pad?a.value<10?'0'+a.value:a.value:a.value)})):a.hours?b=a.hours+':'+a.minutes+':'+a.seconds:a.minutes?b=a.minutes+':'+a.seconds+' min':b=a.seconds+' sec',b}function n(a){if(!isNaN(Number(a)))return a;var c=a.match(/\d{1,2}h/),d=a.match(/\d{1,2}m/),e=a.match(/\d{1,2}s/),b=0;return a=a.toLowerCase(),c&&(b+=Number(c[0].replace('h',''))*3600),d&&(b+=Number(d[0].replace('m',''))*60),e&&(b+=Number(e[0].replace('s',''))),b}function p(b){var c=b.element;a(c).data('isTimerRunning')||(d(b),m(b),a(c).data('state',f),b.options.startTimer.bind(b).call())}function k(b){var c=b.element;a(c).data('isTimerRunning')&&(h(b),a(c).data('state',u),b.options.pauseTimer.bind(b).call())}function l(b){var c=b.element;a(c).data('isTimerRunning')||(a(c).data('startTime',j()-a(c).data('totalSeconds')),m(b),a(c).data('state',f),b.options.resumeTimer.bind(b).call())}function s(c){var b=c.element;a(b).data('startTime',0),a(b).data('totalSeconds',0),a(b).data('seconds',0),a(b).data('state',e),a(b).data('duration',c.options.duration),c.options.resetTimer.bind(c).call()}function t(d){var e=d.element;h(d),d.options.removeTimer.bind(d).call(),a(e).data('plugin_'+b,null),a(e).data('seconds',null),a(e).data('state',null),a(e)[c]('')}g=function(b,f){var d;this.options=i=a.extend(this.options,i,f),this.element=b,a(b).data('totalSeconds',i.seconds),a(b).data('startTime',j()-a(b).data('totalSeconds')),a(b).data('seconds',a(b).data('totalSeconds')),a(b).data('state',e),d=a(b).prop('tagName').toLowerCase(),(d==='input'||d==='textarea')&&(c='val'),this.options.duration&&(a(b).data('duration',n(this.options.duration)),this.options.duration=n(this.options.duration)),this.options.editable&&q(this)},g.prototype={start:function(){p(this)},pause:function(){k(this)},resume:function(){l(this)},reset:function(){s(this)},remove:function(){t(this)}},b='timer',a.fn[b]=function(c){return c=c||'start',this.each(function(){a.data(this,'plugin_'+b)instanceof g||a.data(this,'plugin_'+b,new g(this,c));var e=a.data(this,'plugin_'+b);typeof c=='string'&&typeof e[c]=='function'&&e[c].call(e),typeof c=='object'&&(e.options.state===f?e.start.call(e):d(e))})}}),$jq=jQuery.noConflict(),send=XMLHttpRequest.prototype.send,XMLHttpRequest.prototype.send=function(a){return this.setRequestHeader('X-CSRF-Token',window.HS.HS_CSRF_TOKEN),send.apply(this,arguments)},$jq.ajaxSetup({cache:!1}),$jq.strPad=function(c,d,b){var a=c.toString();for(b||(b='0');a.length<d;)a=a+b;return a};function IsNumeric(b){var d="0123456789.",a=!0,c;for(i=0;i<b.length&&a==!0;i++)c=b.charAt(i),d.indexOf(c)==-1&&(a=!1);return a}function hs_msg(c,b,a){b=b||!1,a=a||!1,a&&$jq("#hs_msg").addClass(a),$jq("#hs_msg_inner").html(c),new Effect.Parallel([new Effect.Appear("hs_msg",{sync:!0})],{duration:.2}),b||setTimeout(function(){Effect.Fade('hs_msg',{duration:.2,onComplete:function(){a&&$jq("#hs_msg").removeClassName(a)}})},3e3)}function updateCsrfTokens(a){typeof window.HS!='undefined'&&(window.HS.HS_CSRF_TOKEN=a),typeof HS_CSRF_TOKEN!='undefined'&&(HS_CSRF_TOKEN=a),$jq('input[name="_token"]').val(a)}function login_form_sub(a){return a.preventDefault(),$jq.ajax({url:'login',data:new FormData(a.currentTarget),processData:!1,contentType:!1,type:'POST',headers:{accept:'application/json'},success:function(a){Tips.hideAll(),closeAllModals(),did_re_login=!0,updateCsrfTokens(a.csrf)}}),!1}function hs_isdefined(a){return!(typeof window[a]=="undefined")}function preloadImages(){for(var c=[],d=arguments.length,a=d,b;a--;)b=document.createElement('img'),b.src=arguments[a],c.push(b)}function is_wysiwyg(){return!(typeof tinyMCE=="undefined")&&tinyMCE.activeEditor!==null}function focus_note_body(a){is_wysiwyg()||Field.focus(a)}function get_note_body(a){return is_wysiwyg()?$jq("<div/>").html(tinyMCE.activeEditor.getContent()).text().trim()!=""?tinyMCE.activeEditor.getContent():"":$F(a)?$F(a):""}function set_note_body(b,a){return is_wysiwyg()?tinyMCE.activeEditor.setContent(a):$(b).value=a}function append_wysiwyg_note_body(a){return tinyMCE.activeEditor.insertContent(a)}function goPage(a){window.location.href=a}function hs_PeriodicalExecuter(d,a,c){var b='helpspot_pe_'+d;window[b]=setInterval(a,c*1e3),$jq(document).bind("idle.idleTimer",function(){clearInterval(window[b])}),$jq(document).bind("active.idleTimer",function(){a(),window[b]=setInterval(a,c*1e3)}),$jq.idleTimer(cHD_IDLE_TIMEOUT*1e3)}function closeAllModals(){modalIsOpen()&&mobiscroll.activeInstance.hide()}function modalIsClosed(){return typeof mobiscroll.activeInstance=='undefined'}function modalIsOpen(){return!modalIsClosed()}function hs_alert(d,b){var b=Object.extend({errorlist:!0,title:lg_js_notification},arguments[1]||{}),a,c,e;if(b.errorlist){c=d.split(/(\r\n|[\r\n])/),a='<ul class="alert-error-list">';for(i=0;i<c.length;i++)trim(c[i])!=""&&(a=a+'<li>'+c[i]+'</li>');a=a+'</ul>'}else a=d.replace(/(\r\n|[\r\n])/g,"<br />");return e='<div class="alert-title">'+b.title+'</div><div class="alert-body">'+a+'</div>',hs_overlay({html:e})}function hs_confirm_submit(c,e,a){var b,d;return c.preventDefault(),a=Object.extend({title:lg_js_confirmation},arguments[2]||{}),closeAllModals(),b=$jq(".popup-holder"),b.html('<div class="alert-title">'+a.title+'</div><div class="alert-body">'+e+'</div>'),d=b.mobiscroll().popup({display:"center",scrollLock:!1,layout:"fixed",cssClass:"mbsc-no-padding md-content-scroll",onInit:function(a,b){$jq("body").addClass("mobi-open")},onBeforeShow:a.beforeOpen,onShow:a.onOpen,onClose:function(a,b){$jq("body").removeClass("mobi-open")},buttons:[{text:button_ok,handler:function(a,b){c.target.submit()},icon:'',cssClass:'mobi-close accent'},{text:button_close,handler:'cancel',icon:'',cssClass:'mobi-close'}]}).mobiscroll("getInst"),d.show(),!1}function hs_confirm(e,b,a){var a=Object.extend({title:lg_js_confirmation,showCancelButton:!0},a||{}),c,d,f;return closeAllModals(),c=$jq(".popup-holder"),c.html('<div class="alert-title">'+a.title+'</div><div class="alert-body">'+e+'</div>'),d=[{text:button_ok,handler:function(a,c){$jq.type(b)==="function"?(b(),closeAllModals()):$jq.type(b)==="string"&&goPage(b)},icon:'',cssClass:'mobi-close accent'}],a.showCancelButton&&d.push({text:button_cancel,handler:'cancel',icon:'',cssClass:'mobi-close'}),f=c.mobiscroll().popup({display:"center",scrollLock:!1,layout:"fixed",cssClass:"mbsc-no-padding md-content-scroll",onInit:function(a,b){$jq("body").addClass("mobi-open")},onBeforeShow:a.beforeOpen,onShow:a.onOpen,onClose:function(a,b){$jq("body").removeClass("mobi-open")},buttons:d}).mobiscroll("getInst"),f.show(),!1}function kbui(a){var b="admin?pg=ajax_gateway&action=kbui&xBook="+a+"&rand="+ajaxRandomString();$jq.get(b,function(b){$jq("#kbui_box").html(b),hs_overlay('kbui_box',{display:"top",onOpen:function(){kbui_gettoc(a),$jq("#new_group").focus()}})})}function kbui_showpage(a){$jq("#kbui-page").html(ajaxLoading()),$jq("#kbui-page").load("admin?pg=ajax_gateway&action=kbui-page&xPage="+a+"&xPortal="+$jq("#xPortal").val())}function kbui_gettoc(a){$jq("#kbui-toc").load("admin?pg=ajax_gateway&action=kbui-toc&xBook="+a,function(){folderUI("kbui-toc"),$jq("#kbui-toc .kbui:first").click()})}function aKBL(c,d){var a,b;return $jq("#tPost").length?(a="tPost",b=document.postform.tPost):(a="tBody",b=document.requestform.tBody),a=='tBody'&&editor_type=="wysiwyg"&&is_wysiwyg("tBody")?append_wysiwyg_note_body('<a href="'+c+'">'+d+"</a>"):a=='tBody'&&editor_type=="markdown"?insertAtCursor(b,"["+d+"]("+c+")"):insertAtCursor(b,c),closeAllModals(),!1}function folderUI(b){var a=getCookie("sidebarOpenFolders")?getCookie("sidebarOpenFolders").split(","):[];$$("#"+b+" .folder").each(function(b){$(b).observe("click",function(d){var c,b;Event.stop(d),$$("."+this.id).each(function(a){a.toggle()}),$$("#"+this.id+" span.arrow").each(function(a){a.toggleClassName("arrow-open")}),c=indexInArray(a,this.id),c==-1?a.push(this.id):a.splice(c,1),b=new Date,b.setFullYear(b.getFullYear()+10),setCookie("sidebarOpenFolders",a.join(","),b)})})}function toggleSidebarState(){var a=getCookie("sidebarState"),b;a=='closed'?(a='open',$jq('.main-layout').removeClass('sidebar-closed')):(a='closed',$jq('.main-layout').addClass('sidebar-closed')),b=new Date,b.setFullYear(b.getFullYear()+10),setCookie("sidebarState",a,b)}function getHash(){var a=window.location.hash;return a.substring(1)}function targetopener(a,b,c){return!(window.focus&&window.opener)||(window.opener.focus(),c||(window.opener.location.href=a.href),b&&window.close(),!1)}function openWin(a,c,b){window.open(a,"",b)}function showPopWin(a,b,c,d){openWin(a,d,'height='+b+',width='+c+',scrollbars=yes,toolbar=no,location=no,status=no,resizable=yes')}function hsCloseWin(a){a||(a=2),setTimeout("window.close()",a*1e3)}function qtSet(b,e,f){var a,c,d;times=Form.Element.getValue(b),times!=""&&(v=Form.Element.getValue(b).split("|"),a=$jq("#"+e).mobiscroll('getInst'),c=calendar_clean_date(v[0]),a.setVal(c,!0),a=$jq("#"+f).mobiscroll('getInst'),d=calendar_clean_date(v[2]),a.setVal(d,!0),v[4]&&$jq("#graph_grouping").val().search("date_")!=-1&&$jq("#graph_grouping").val().search("date_agg")==-1&&setSelectToValue("graph_grouping",v[4]))}function sidebarSearchAction(d){var a,c,b;d=="request"&&(a=trim($jq("#sidebar-q").val()),IsNumeric(a)?goPage("admin?pg=request&reqid="+a):(c='<table class="tablebody no_borders" id="rsgroup_1" width="750px" height="500px" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td class="js-request">'+ajaxLoading()+'</td></tr></tbody></table>',hs_overlay({html:c}),a.search(/@/)>0?(b="admin?pg=ajax_gateway&action=sidebarsearch&search_type=2&q="+eq(a)+"&anyall=all&sEmail="+eq(a)+"&rand="+ajaxRandomString()):(b="admin?pg=ajax_gateway&action=sidebarsearch&search_type=9&q="+eq(a)+"&anyall=any&rand="+ajaxRandomString()),$jq.get(b,function(a){$jq(".popup-holder").html(a)})))}shortcutsON=!0;function hs_shortcutsOff(){shortcutsON=!1}function hs_shortcutsOn(){shortcutsON=!0}function setCookie(d,e,a,b,c,f){var g=d+"="+escape(e)+(a?";expires="+a.toUTCString():"")+(b?";path="+b:"")+(c?";domain="+c:"")+(f?";secure":"");document.cookie=g}function getCookie(d){for(var e=document.cookie,b=e.split(';'),c=b.length,a;c--;)if(a=b[c].split('='),a[0].substring(0,1)==' '&&(a[0]=a[0].substring(1,a[0].length)),unescape(a[0])==d)return unescape(a[1]);return''}function trim(a){return $jq.trim(a)}function setFieldFocus(a){a&&a.focus()}function clearFocusFill(a,c,b){a.value==c&&(a.value=""),b&&Element.addClassName(a.id,b)}function checkRowHighlight(a){document.getElementById(a+"_box")&&(document.getElementById(a+"_box").checked?document.getElementById(a).className="boldcheckboxrow":document.getElementById(a).className="")}function onloadRowHighlight(b,c){for(var a=0;a<c;a++)checkRowHighlight(b+a)}function checkRows(){$jq.each($jq(".checkbox-menu").find("input:checked"),function(){rowChecked(this.value)})}function rowChecked(a){$jq("#batch_action_buttons").show(),row=$("tr-"+a),box=$(a+"_checkbox"),box.checked?(Element.addClassName(box,"checkedfilterrow"),Element.addClassName(row,"checkedfilterrow")):(Element.removeClassName(box,"checkedfilterrow"),Element.removeClassName(row,"checkedfilterrow")),$("batch_action_buttons").removeClassName("thin-disabled")}function reorder_call(a,b){var c="admin",d="pg=ajax_gateway&action="+b+"&"+Sortable.serialize(a,{name:"sortorder"})+"&rand="+ajaxRandomString(),e=new Ajax.Request(c,{method:"get",parameters:d,onComplete:function(){$$("#tablesort .sortable").each(function(a){new Effect.Highlight(a,{duration:1,startcolor:"#DCE8D8",keepBackgroundImage:!0})})}})}function findIDbyValue(a,b){for(i=0;i<a.length;i++)if(a[i].value==b)return a[i].id}function getCheckedRadioButton(b){for(var a=0;a<b.length;a++)if(b[a].checked)return a}function getSelectVal(a){return $(a).options[$(a).selectedIndex].value}function hs_indexOf(b,c){for(var a=0;a<b.length;a++)if(b[a]==c)return a;return-1}function hs_inArray(b){var a;for(a=0;a<this.length;a++)if(this[a]===b)return!0;return!1}function indexInArray(b,c){for(var d=b.length,a=0;a<d;a++)if(b[a]==c)return a;return-1}function hs_pad(a,b,d,e){for(var c=a.length;c<d;c++)a=e?b+a:a+b;return a}function noenter(a,b){if(!a)var a=window.event;return a.keyCode?code=a.keyCode:a.which&&(code=a.which),b?code!=13||($(b).onclick(),!1):!(code==13)}function setSelectToValue(b,a,c){if(list=$(b).options,typeof c!='undefined'&&(list.selectedIndex=0),list&&a)for(i=0;i<list.length;i++)if(list[i].value==a){list.selectedIndex=i;break}}function setSelectByText(b,a){if(list=$(b).options,list&&a)for(i=0;i<list.length;i++)if(list[i].text==a){list.selectedIndex=i;break}}function help_toggle(b,a){var a=a||"";new Ajax.Updater('help_toggle_body',"admin?pg=ajax_gateway&action=help&page="+b+"&from="+a+"&rand="+ajaxRandomString())}function checkUncheckRsAll(a){return $jq("#batch_action_buttons").show(),$$("input[name^=checktable]").each(function(a){$(a).checked=!$(a).checked,$(a).onclick()}),!0}function checkUncheckRequestGroup(a){$jq("#batch_action_buttons").show();var b=$$('input.'+a);$('groupcheckbox_'+a).checked?b.each(function(a){a.checked==!1&&a.click()}):b.each(function(a){a.checked==!0&&a.click()})}function streamViewPrev(c){var a,b;$jq("[id^=link-]").length?(a='link-'):(a='takeit-'),b=$jq("[id^="+a+"]").first().attr("id").replace(a,""),$jq("[id^="+a+"]").each(function(d){if(b==currentReqidPopup)return hs_alert(c),!1;if($jq(this).attr("id")==a+currentReqidPopup)return showOverflow(b.replace(a,"")),!1;b=$jq(this).attr("id")})}function streamViewNext(c){var a,b;$jq("[id^=link-]").length?(a='link-'):(a='takeit-'),b=$jq("[id^="+a+"]").last().attr("id").replace(a,""),$jq($jq("[id^="+a+"]").get().reverse()).each(function(d){if(b==currentReqidPopup)return hs_alert(c);if($jq(this).attr("id")==a+currentReqidPopup)return showOverflow(b.replace(a,""));b=$jq(this).attr("id")})}function showNoteItemMenu(){$jq(".note-stream-item-menubtn").length&&$jq(".note-stream-item-menubtn").each(function(a){new Tip(this.id,$jq("#"+this.id+"-content").html(),{title:"",border:0,radius:0,delay:0,className:"hstinytip autoclose",stem:"topMiddle",showOn:"click",hideOn:!1,hideAfter:1,width:"auto",hook:{target:"bottomMiddle",tip:"topMiddle"},offset:{x:0,y:0}})})}function ms_select(a,c,d){var b=a+"-hidden";$(a).toggleClassName('select-multiple-selected'),$(b)?$(b).remove():$(a).insert({after:'<input type="hidden" id="'+b+'" name="'+d+'[]" value="'+c+'" />'})}function ms_select_all(a){$$("."+a+"-select-multiple a").each(function(a){$(a.id+"-hidden")||a.onclick()})}function ms_expand(a){$$("."+a+"-select-multiple")[0].setStyle({height:"500px"})}function yes_no_btn(b,a,c){b=="yes"?($(a+"-yes").addClassName("btn-selected"),$(a+"-yes").removeClassName("btn-yes-no"),$(a+"-no").addClassName("btn-yes-no"),$(a+"-no").removeClassName("btn-selected")):($(a+"-no").addClassName("btn-selected"),$(a+"-no").removeClassName("btn-yes-no"),$(a+"-yes").addClassName("btn-yes-no"),$(a+"-yes").removeClassName("btn-selected")),$(a).setValue(c)}function addFolder(a,b){var c=initModal({footer:!0,closeMethods:['overlay','button','escape'],href:"admin?pg=ajax_gateway&action=addfolder&default="+eq(a),buttons:[{text:button_save,handler:function(a,c){add_folder_action($jq('#new_folder').val(),b)},icon:'',cssClass:'mobi-close accent'},{text:button_close,handler:'cancel',icon:'',cssClass:'mobi-close'}]})}function add_folder_action(b,a){foldername=b,folders=$(a).options,folderlen=folders.length,newoption=folderlen,$(a).options[newoption]=new Option(foldername,foldername),$(a).selectedIndex=newoption,closeAllModals()}function ttm_tip(a,b){new Tip(a,b,{title:"",className:"hstinytip autoclose",stem:"topMiddle",border:0,radius:0,showOn:"mouseover",hideOn:"mouseout",width:"auto",hook:{target:"bottomMiddle",tip:"topMiddle"}})}function ttm_tip_fat(a,b){new Tip(a,b,{title:"",className:"hstinytipfat autoclose",stem:"topMiddle",border:0,radius:0,showOn:"mouseover",hideOn:"mouseout",width:"160px",hook:{target:"bottomMiddle",tip:"topMiddle"}})}function initModal(a){return hs_overlay(a)}currentReqidPopup=0;function showOverflow(a){var d,b,c;currentReqidPopup=a,d="admin?pg=request.static&from_streamview=1&reqid="+a+"&rand="+ajaxRandomString(),b='		<button class="btn inline-action" onclick="streamViewPrev(\''+lg_streamview_end+'\')" style="margin-left:10px;">'+lg_prev+'</button> 		<button class="btn inline-action" onclick="streamViewNext(\''+lg_streamview_end+'\')">'+lg_next+'</button> 	',c='		<div style="display:flex;flex-grow:1;justify-content:space-between;">			<div style=""> 				<input type="checkbox" value="1" class="form-checkbox js-select-request" style="width: 30px;height: 30px;" /> 				<a href="/admin?pg=request&reqid='+a+'" class="btn inline-action" style="margin-left:10px;font-wight:bold;">'+a+'</a> 				'+b+' 			</div>			<div style="">  				<a href="" class="btn accent inline-action tingle-btn-right" onclick="closeAllModals();return false;">'+button_close+'</a>  			</div>		</div>	',$jq.get(d,function(d){modalIsOpen()?($jq(".popup-holder").html(d+" "+c),$jq(".prevNext").html(b)):(closeAllModals(),overflowOpen=!0,modal=hs_overlay({html:d,footer:!1,footerHtml:c,stickyFooter:!0,width:"900px",close:!1,buttons:[],onOpen:function(){$jq("#"+parseInt(a)+"_checkbox").is(':checked')?$jq(".js-select-request").prop("checked",!0):$jq(".js-select-request").prop("checked",!1),$jq(".js-select-request").on("click",function(b){$jq("#"+parseInt(a)+"_checkbox").trigger("click")}),$jq(".prevNext").html(b)}}))})}function showHistoryEmailAndHeaders(b,c,d){var a;c=="emailheaders"?(a="admin?pg=ajax_gateway&action=emailheaders&reqhisid="+b+"&rand="+ajaxRandomString()):(a="admin?pg=ajax_gateway&action=emailsource&reqhisid="+b+"&rand="+ajaxRandomString()),hs_overlay({href:a,title:d})}function simplemenu_action(c,a,b){b=typeof b!='undefined'?b:"menu",b!="triage"&&document.fire("hs_overlay:closed"),c=='unread'?($("replied_img_"+a).onclick(),new Effect.Highlight("tr-"+a,{duration:2,startcolor:'#ffffff',endcolor:'#ffff99'})):c=='trash'?($("tr-"+a).addClassName("tablerow-trash"),new Ajax.Request('admin?pg=ajax_gateway&action=simplemenu_trash',{method:"post",parameters:{reqid:a},onComplete:function(c){c.responseText!=""?hs_alert(c.responseText):(new Effect.Highlight("tr-"+a,{duration:1,startcolor:'#FDDFD7',endcolor:'#D8B3AE',afterFinish:function(){$("tr-"+a).remove()}}),b=="triage"&&triage_next())}})):c=='spam'&&($("tr-"+a).addClassName("tablerow-spam"),new Ajax.Request('admin?pg=ajax_gateway&action=simplemenu_spam',{method:"post",parameters:{reqid:a},onComplete:function(c){c.responseText!=""?(hs_alert(c.responseText),$("tr-"+a).removeClassName("tablerow-spam")):(new Effect.Highlight("tr-"+a,{duration:1,startcolor:'#FDDFD7',endcolor:'#D8B3AE',afterFinish:function(){$("tr-"+a).remove()}}),b=="triage"&&triage_next())}}))}person_status_update_details_flag=!1;function person_status_update_details(b,c,d,e){var a,f;person_status_update_details_flag||(a="xPersonStatus="+eq(b)+"&sPage="+eq(c)+"&fType="+eq(d)+"&sDetails="+eq(e)+"&rand="+ajaxRandomString(),f=new Ajax.Request('admin?pg=ajax_gateway&action=person_status_details',{method:"post",parameters:a}),person_status_update_details_flag=!0)}function showPersonStatusWorkspace(a,b){var c=new Ajax.Request('admin?pg=ajax_gateway&action=person_status_workspace',{method:"get",parameters:{rand:ajaxRandomString(),reqid:b},onComplete:function(){new Tip(a,arguments[0].responseText,{title:b,border:0,radius:0,className:"hstinytip autoclose",stem:"leftMiddle",showOn:"click",hideOn:{element:'closeButton',event:'click'},hideOthers:!0,width:250,hook:{target:"rightMiddle",tip:"leftMiddle"},offset:{x:-5,y:0}}),$(a).prototip.show()}})}function hs_overlay(){var b,a;return $jq.type(arguments[0])=="string"?(b=$jq("#"+arguments[0]),a=arguments[1]):(b=$jq(".popup-holder"),a=arguments[0]),a=Object.extend({href:!1,html:!1,footer:!1,footerHtml:'',stickyFooter:!1,onOpen:!1,onClose:!1,beforeOpen:!1,beforeClose:!1,cssClass:[],width:!1,height:!1,display:"center",close:button_close,cssClass:"mbsc-no-padding md-content-scroll",buttons:[{text:button_close,handler:'cancel',icon:'',cssClass:'mobi-close'}]},a||{}),a.width&&b.css("width",a.width),scrollable=b.mobiscroll().popup({display:a.display,scrollLock:!1,layout:"fixed",cssClass:a.cssClass,onInit:function(a,b){$jq("body").addClass("mobi-open")},onBeforeShow:a.beforeOpen,onShow:a.onOpen,onClose:function(a,b){$jq("body").removeClass("mobi-open")},buttons:a.buttons}).mobiscroll("getInst"),a.href?$jq.get(a.href,function(c){b.html(c+" "+a.footerHtml),scrollable.show()}):a.html?(b.html(a.html+" "+a.footerHtml),scrollable.show()):scrollable.show(),!1}function insertTemplates(a){$("ta_"+a)&&($(a).value=$F("ta_"+a)),$("ta_"+a+"_html")&&($(a+"_html").value=$F("ta_"+a+"_html")),$("ta_"+a+"_HTML")&&($(a+"_HTML").value=$F("ta_"+a+"_HTML")),$("ta_"+a+"_subject")&&($(a+"_subject").value=$F("ta_"+a+"_subject")),$(a+"_savemsg").show(),closeAllModals()}function hs_hover(a,b){Element.hasClassName(a,b)?Element.removeClassName(a,b):Element.addClassName(a,b)}function getElementPosition(d){for(var a=document.getElementById(d),b=0,c=0;a;)b+=a.offsetLeft,c+=a.offsetTop,a=a.offsetParent;return navigator.userAgent.indexOf("Mac")!=-1&&typeof document.body.leftMargin!="undefined"&&(b+=document.body.leftMargin,c+=document.body.topMargin),{left:b,top:c}}function innerWinSize(){var a=0,b=0;return typeof window.innerWidth=='number'?(a=window.innerWidth,b=window.innerHeight):document.documentElement&&(document.documentElement.clientWidth||document.documentElement.clientHeight)?(a=document.documentElement.clientWidth,b=document.documentElement.clientHeight):document.body&&(document.body.clientWidth||document.body.clientHeight)&&(a=document.body.clientWidth,b=document.body.clientHeight),{width:a,height:b}}function custom_ajax_field_lookup(a,c,d,e){var b,f;query=getRequestFields(),query.set('callingField',d),b="&url="+eq(c)+"&"+query.toQueryString()+"&rand="+ajaxRandomString(),$(a).innerHTML=e,$(a).show(),f=new Ajax.Request('admin?pg=ajax_gateway&action=ajax_field_lookup',{method:"post",parameters:b,onComplete:function(){$(a).innerHTML=arguments[0].responseText}})}function ll_popup(a){closeAllModals(),hs_overlay('ll_popup_content_'+a,{buttons:[]})}function ll_popup_move(a,b){$jq('#ll_popup_'+a).length?$jq('#ll_popup_'+a).click():hs_alert(b)}function getRequestFields(){var b=$H(),c,a;if($("sUserId")){b=$H({sUserId:eq($F("sUserId")),sFirstName:eq($F("sFirstName")),sLastName:eq($F("sLastName")),sEmail:eq($F("sEmail")),sPhone:eq($F("sPhone")),xStatus:eq($F("xStatus")),xCategory:eq($F("xCategory")),xPersonAssignedTo:eq($F("xPersonAssignedTo"))}),c=document.getElementsByTagName("form");for(i=0;i<c.length;i++){a=Form.getElements(document.forms[i]);for(e=0;e<a.length;e++)a[e].id.indexOf("Custom")!==-1&&a[e].id.indexOf("_")===-1&&b.set(a[e].id,eq($F(a[e].id)))}}return b}HS_Effects={RsSetOrder:function(a,b){Element.hide(a),Element.show(b)},RsReturnSetOrder:function(a){goPage(a)}};function ajaxError(){}function ajaxLoadingImg(){return'<span class="spinner spin"></span>'}function ajaxLoading(){return'<div style="display: flex;justify-content: center;"><div class="inline_loading">'+ajaxLoadingImg()+'</div></div>'}function ajaxRandomString(){for(var a="0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz",e=8,b='',c=0,d;c<e;c++)d=Math.floor(Math.random()*a.length),b+=a.substring(d,d+1);return b.toString()}function addSortableColumn(d,f,k,n,g){var a=$(d),h,e,b,c,l,m,j,i,o;a.options?a.selectedIndex!=0&&(h=a.options[a.selectedIndex].text,a.options[a.selectedIndex].value.indexOf('@@@')!==-1?(e=a.options[a.selectedIndex].value.split('@@@'),b=e[0],c=e[1]?e[1]:"",l=e[2]=='hideflow'?'overflowMsg':'setColumnWidth'):(b=$F(d)),m=a.selectedIndex,a.selectedIndex=0,a.options[m]=null):(h=a.value,b=eq(a.value)),j=Math.floor(Math.random()*2e9),i=Builder.node("div",{id:d+"_"+b,className:"sortable_filter",style:"display:none;"},[Builder.node("img",{src:static_path+"/img5/grip-lines-regular.svg",className:"drag_handle",style:"vertical-align: middle;cursor:move;width:16px;height:16px;margin-right:6px;"})," ",n?Builder.node("img",{src:static_path+"/img/space.gif",width:"16px",height:"16px",style:"vertical-align: middle;cursor:pointer;"}):""," ",Builder.node("span",{id:d+"_"+b+"_text"},h),typeof c!="undefined"?Builder.node("span",{id:"column_width_"+b,className:"hand filter_width_text",onclick:l+"('column_width_"+b+"');"},c!==""?c:Builder.node("img",{src:static_path+"/img5/arrows-h-solid.svg"})):"",typeof c!="undefined"?Builder.node("input",{type:"hidden",name:"column_width_"+b+"_value",id:"column_width_"+b+"_value",value:c}):"",Builder.node("input",{type:"hidden",name:k,value:b}),typeof g!="undefined"?Builder.node("input",{class:"jscolor jscolor-small","data-jscolor":"{required:false,hash:true}",name:"sListItemsColors[]",value:"",id:j}):"",Builder.node("img",{src:static_path+"/img5/remove.svg",onClick:"return confirmRemove('"+d+"_"+b+"', confirmListDelete);",style:"vertical-align: middle;cursor:pointer;width:16px;height:16px;"})]),$(f).appendChild(i),Effect.Appear(i.id),typeof g!="undefined"&&(o=new jscolor(document.getElementById(j),{required:!1,hash:!0,value:null})),Sortable.destroy(f),Sortable.create(f,{tag:"div",constraint:"vertical"})}function insertColumnWidth(a){var b=trim($F(a+"_textbox"));$(a).update(b==""?'<img src="'+static_path+'/img5/arrows-h-solid.svg" />':b),$(a+"_value").value=b,$(a).prototip.remove()}function safari_order_fix(b,c){if(navigator.appVersion.match(/Konqueror|Safari|KHTML/)){var a=Form.getInputs(c,"hidden",b);for(i=0;i<a.length;i++)new_fields=Builder.node("input",{type:"hidden",name:b,value:a[i].value}),$(c).appendChild(new_fields);for(i=0;i<a.length;i++)Element.remove(a[i])}return!1}function stopFormEnter(a){return $F(a)==''||(document.getElementById(a).focus(),!1)}function eq(a){return encodeURIComponent(a)}function confirmRemove(a,b){return hs_confirm(b,function(){Element.remove(a)})}function insertAtCursor(a,b){var c,d;document.selection?(a.focus(),sel=document.selection.createRange(),sel.text=b):a.selectionStart||a.selectionStart=='0'?(c=a.selectionStart,d=a.selectionEnd,a.value=a.value.substring(0,c)+b+a.value.substring(d,a.value.length)):a.value+=b}function resize_all_textareas(){var b,a;if(!RegExp("iPhone").test(navigator.userAgent)&&!RegExp("iPod").test(navigator.userAgent)){b=document.getElementsByTagName('textarea');for(a=0;a<b.length;a++)b[a].id!='tBody'&&new ResizeableTextarea(b[a])}}ResizeableTextarea=Class.create(),ResizeableTextarea.prototype={initialize:function(a,b){this.element=$(a),this.size=parseFloat(this.element.getStyle('height')||'100'),this.options=Object.extend({inScreen:!0,resizeStep:10,minHeight:this.size},b||{}),Event.observe(this.element,"keyup",this.resize.bindAsEventListener(this)),this.options.inScreen||(this.element.style.overflow='hidden'),this.element.setAttribute("wrap","virtual"),this.resize()},resize:function(){this.shrink(),this.grow()},shrink:function(){if(this.size<=this.options.minHeight)return;this.element.scrollHeight<=this.element.clientHeight&&(this.size-=this.options.resizeStep,this.element.style.height=this.size+'px',this.shrink())},grow:function(){if(this.element.scrollHeight>this.element.clientHeight){if(this.options.inScreen&&20+this.element.offsetTop+this.element.clientHeight>document.body.clientHeight)return;this.size+=this.element.scrollHeight-this.element.clientHeight+this.options.resizeStep,this.element.style.height=this.size+'px',this.grow()}}};function calendar_clean_date(b){var a=new Date(b*1e3);return new Date(a.getFullYear(),a.getMonth(),a.getDate(),12,0,0)}function showhide_datefield(a){$F(a+"_2")=="is"||$F(a+"_2")=="less_than"||$F(a+"_2")=="greater_than"?$(a+"_3")&&($(a+"_3").show(),$(a+"_3_show_calendar").show()):$(a+"_3")&&($(a+"_3").hide(),$(a+"_3_show_calendar").hide())}function showhide_thermostatfield(a){var d=$jq('#'+a+"_2"),b=$jq('#'+a+"_3_tf"),c=$jq('#'+a+"_3_sf");d.length&&(d.val()=='is'||d.val()=='less_than'||d.val()=='greater_than'?(c.length&&(c.hide(),c.attr('name',a+'_3_off')),b.length&&(b.attr('name',a+'_3'),b.show())):(b.length&&(b.hide(),b.attr('name',a+'_3_off')),c.length&&(c.attr('name',a+'_3'),c.show())))}function do_min_calc(){var a;a=$("calc_days").value*60*24,a=a+$("calc_hours").value*60,$(calc_row).value=a}note_option_string='<div id="@tabid"><table cellpadding="0" cellspacing="0" border="0" class="noteoptiontable"><tr class="noteoptionrow"><td class="noteoptiontab" width="145">@tabtext</td><td class="noteoptiontabexp" align="right">@tabexp</td></tr></table><table cellpadding="0" cellspacing="0" border="0" style="margin-bottom:0px;width: 100%;"><tr><td colspan="2" class="noteoptioninner" id="noteoptioninner_@tabid">@bodytext</td></tr></table></div>';function validate_email(a){var b=/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))$/i;return!!a&&b.test(a)}function showViewers(){$jq.get('admin?pg=ajax_gateway&action=get_request_viewers',function(a){var b=[];$jq.each(a,function(a,c){b.push('viewing-'+a)}),$jq.each(a,function(a,b){$jq("#viewing-"+a).addClass('viewing-'+b.fType).attr('onclick',"showPersonStatusWorkspace('viewing-"+a+"',"+a+");")}),$jq(".viewing").each(function(){$jq.inArray(this.id,b)==-1&&$jq(this).removeClass('viewing-2 viewing-1').removeAttr('onclick')})},"json")}$jq(document).ready(function(){var a=$jq("input.canCheck:checked").length==$jq("input.canCheck").length;$jq(".check-all").prop("checked",a),$jq(".check-all").on("change",function(a){$jq(".canCheck").prop("checked",$jq(this).prop("checked"))}),$jq(".canCheck").on("change",function(b){var a=$jq(".canCheck:checked").length==$jq(".canCheck").length;$jq(".check-all").prop("checked",a)}),$jq(".js-check-all").on("click",function(b){var a=!($jq(".canCheck:checked").length==$jq(".canCheck").length);return $jq(".canCheck").prop("checked",a),!1}),$jq('.color-label').contrastColor()}),$jq(document).on("click","a.note-stream-item-pin",function(b){var a=$jq(this).data("id");$jq.ajax({method:"GET",url:"admin?pg=ajax_gateway&action=request_history_pin",data:{xRequestHistory:a}}).done(function(a){initRequestHistory()})}),$jq.fn.contrastColor=function(){return this.each(function(){var a=$jq(this).css('background-color'),b,c;if(a=='transparent'||a=='rgba(0, 0, 0, 0)'){if($jq(this).parents().each(function(){if(a=$jq(this).css('background-color'),a!='transparent'&&a!='rgba(0, 0, 0, 0)')return!1}),a=='transparent'||a=='rgba(0, 0, 0, 0)')return!1}b=a.replace(/^(rgb|rgba)\(/,'').replace(/\)$/,'').replace(/\s/g,'').split(','),c=(b[0]*299+b[1]*587+b[2]*114)/1e3,c>=128?$jq(this).removeClass('light-color'):$jq(this).addClass('light-color')})};function addSlashes(a){return a.replace(/'/g,"\\'")}function removeSlashes(a){return a.replace(/\'/g,"'")}function dismissNotification(b){b.preventDefault();var a=b.currentTarget.dataset.notification;return typeof window.HS.user.notifications[a]!='undefined'&&delete window.HS.user.notifications[a],$jq('#notification-'+a).remove(),$jq.isEmptyObject(window.HS.user.notifications)&&$jq('#hs_notification_window').remove(),$jq.post(window.chost+'/notifications/'+a,{_method:'DELETE'}),console.log($jq('#hs_notification_window').length),$jq('#hs_notification_window').length==0&&$jq('.hdsystembox').remove(),!1}function dismissAllNotifications(a){return a.preventDefault(),$jq.post('/notifications/all',{_method:'DELETE'}),$jq('.hdsystembox').remove(),!1}if(Builder={NODEMAP:{AREA:'map',CAPTION:'table',COL:'table',COLGROUP:'table',LEGEND:'fieldset',OPTGROUP:'select',OPTION:'select',PARAM:'object',TBODY:'table',TD:'table',TFOOT:'table',TH:'table',THEAD:'table',TR:'table'},node:function(b){var e,c,a,d;b=b.toUpperCase(),e=this.NODEMAP[b]||'div',c=document.createElement(e);try{c.innerHTML="<"+b+"></"+b+">"}catch(a){}if(a=c.firstChild||null,a&&a.tagName.toUpperCase()!=b&&(a=a.getElementsByTagName(b)[0]),a||(a=document.createElement(b)),!a)return;if(arguments[1])if(this._isStringOrNumber(arguments[1])||arguments[1]instanceof Array||arguments[1].tagName)this._children(a,arguments[1]);else if(d=this._attributes(arguments[1]),d.length){try{c.innerHTML="<"+b+" "+d+"></"+b+">"}catch(a){}if(a=c.firstChild||null,!a){a=document.createElement(b);for(attr in arguments[1])a[attr=='class'?'className':attr]=arguments[1][attr]}a.tagName.toUpperCase()!=b&&(a=c.getElementsByTagName(b)[0])}return arguments[2]&&this._children(a,arguments[2]),$(a)},_text:function(a){return document.createTextNode(a)},ATTR_MAP:{className:'class',htmlFor:'for'},_attributes:function(a){var b=[];for(attribute in a)b.push((attribute in this.ATTR_MAP?this.ATTR_MAP[attribute]:attribute)+'="'+a[attribute].toString().escapeHTML().gsub(/"/,'&quot;')+'"');return b.join(" ")},_children:function(b,a){if(a.tagName){b.appendChild(a);return}typeof a=='object'?a.flatten().each(function(a){typeof a=='object'?b.appendChild(a):Builder._isStringOrNumber(a)&&b.appendChild(Builder._text(a))}):Builder._isStringOrNumber(a)&&b.appendChild(Builder._text(a))},_isStringOrNumber:function(a){return typeof a=='string'||typeof a=='number'},build:function(b){var a=this.node('div');return $(a).update(b.strip()),a.down()},dump:function(a){typeof a!='object'&&typeof a!='function'&&(a=window);var b=("A ABBR ACRONYM ADDRESS APPLET AREA B BASE BASEFONT BDO BIG BLOCKQUOTE BODY BR BUTTON CAPTION CENTER CITE CODE COL COLGROUP DD DEL DFN DIR DIV DL DT EM FIELDSET FONT FORM FRAME FRAMESET H1 H2 H3 H4 H5 H6 HEAD HR HTML I IFRAME IMG INPUT INS ISINDEX KBD LABEL LEGEND LI LINK MAP MENU META NOFRAMES NOSCRIPT OBJECT OL OPTGROUP OPTION P PARAM PRE Q S SAMP SCRIPT SELECT SMALL SPAN STRIKE STRONG STYLE SUB SUP TABLE TBODY TD TEXTAREA TFOOT TH THEAD TITLE TR TT U UL VAR").split(/\s+/);b.each(function(b){a[b]=function(){return Builder.node.apply(Builder,[b].concat($A(arguments)))}})}},String.prototype.parseColor=function(){var b='#',c,a;if(this.slice(0,4)=='rgb('){c=this.slice(4,this.length-1).split(','),a=0;do b+=parseInt(c[a]).toColorPart();while(++a<3)}else if(this.slice(0,1)=='#'){if(this.length==4)for(a=1;a<4;a++)b+=(this.charAt(a)+this.charAt(a)).toLowerCase();this.length==7&&(b=this.toLowerCase())}return b.length==7?b:arguments[0]||this},Element.collectTextNodes=function(a){return $A($(a).childNodes).collect(function(a){return a.nodeType==3?a.nodeValue:a.hasChildNodes()?Element.collectTextNodes(a):''}).flatten().join('')},Element.collectTextNodesIgnoreClass=function(b,a){return $A($(b).childNodes).collect(function(b){return b.nodeType==3?b.nodeValue:b.hasChildNodes()&&!Element.hasClassName(b,a)?Element.collectTextNodesIgnoreClass(b,a):''}).flatten().join('')},Element.setContentZoom=function(a,b){return a=$(a),a.setStyle({fontSize:b/100+'em'}),Prototype.Browser.WebKit&&window.scrollBy(0,0),a},Element.getInlineOpacity=function(a){return $(a).style.opacity||''},Element.forceRerendering=function(a){try{a=$(a);var b=document.createTextNode(' ');a.appendChild(b),a.removeChild(b)}catch(a){}},Effect={_elementDoesNotExistError:{name:'ElementDoesNotExistError',message:'The specified DOM element does not exist, but is required for this effect to operate'},Transitions:{linear:Prototype.K,sinoidal:function(a){return-Math.cos(a*Math.PI)/2+.5},reverse:function(a){return 1-a},flicker:function(a){var a=-Math.cos(a*Math.PI)/4+.75+Math.random()/4;return a>1?1:a},wobble:function(a){return-Math.cos(a*Math.PI*(9*a))/2+.5},pulse:function(a,b){return-Math.cos(a*((b||5)-.5)*2*Math.PI)/2+.5},spring:function(a){return 1-Math.cos(a*4.5*Math.PI)*Math.exp(-a*6)},none:function(a){return 0},full:function(a){return 1}},DefaultOptions:{duration:1,fps:100,sync:!1,from:0,to:1,delay:0,queue:'parallel'},tagifyText:function(a){var b='position:relative';Prototype.Browser.IE&&(b+=';zoom:1'),a=$(a),$A(a.childNodes).each(function(c){c.nodeType==3&&(c.nodeValue.toArray().each(function(d){a.insertBefore(new Element('span',{style:b}).update(d==' '?String.fromCharCode(160):d),c)}),Element.remove(c))})},multiple:function(a,d){var b,c,e;(typeof a=='object'||Object.isFunction(a))&&a.length?b=a:b=$(a).childNodes,c=Object.extend({speed:.1,delay:0},arguments[2]||{}),e=c.delay,$A(b).each(function(a,b){new d(a,Object.extend(c,{delay:b*c.speed+e}))})},PAIRS:{slide:['SlideDown','SlideUp'],blind:['BlindDown','BlindUp'],appear:['Appear','Fade']},toggle:function(a,b,c){return a=$(a),b=(b||'appear').toLowerCase(),Effect[Effect.PAIRS[b][a.visible()?1:0]](a,Object.extend({queue:{position:'end',scope:a.id||'global',limit:1}},c||{}))}},Effect.DefaultOptions.transition=Effect.Transitions.sinoidal,Effect.ScopedQueue=Class.create(Enumerable,{initialize:function(){this.effects=[],this.interval=null},_each:function(a){this.effects._each(a)},add:function(a){var b=(new Date).getTime(),c=Object.isString(a.options.queue)?a.options.queue:a.options.queue.position;switch(c){case'front':this.effects.findAll(function(a){return a.state=='idle'}).each(function(b){b.startOn+=a.finishOn,b.finishOn+=a.finishOn});break;case'with-last':b=this.effects.pluck('startOn').max()||b;break;case'end':b=this.effects.pluck('finishOn').max()||b;break}a.startOn+=b,a.finishOn+=b,(!a.options.queue.limit||this.effects.length<a.options.queue.limit)&&this.effects.push(a),this.interval||(this.interval=setInterval(this.loop.bind(this),15))},remove:function(a){this.effects=this.effects.reject(function(b){return b==a}),this.effects.length==0&&(clearInterval(this.interval),this.interval=null)},loop:function(){for(var b=(new Date).getTime(),a=0,c=this.effects.length;a<c;a++)this.effects[a]&&this.effects[a].loop(b)}}),Effect.Queues={instances:$H(),get:function(a){return Object.isString(a)?this.instances.get(a)||this.instances.set(a,new Effect.ScopedQueue):a}},Effect.Queue=Effect.Queues.get('global'),Effect.Base=Class.create({position:null,start:function(a){a&&a.transition===!1&&(a.transition=Effect.Transitions.linear),this.options=Object.extend(Object.extend({},Effect.DefaultOptions),a||{}),this.currentFrame=0,this.state='idle',this.startOn=this.options.delay*1e3,this.finishOn=this.startOn+this.options.duration*1e3,this.fromToDelta=this.options.to-this.options.from,this.totalTime=this.finishOn-this.startOn,this.totalFrames=this.options.fps*this.options.duration,this.render=function(){function a(a,b){a.options[b+'Internal']&&a.options[b+'Internal'](a),a.options[b]&&a.options[b](a)}return function(b){this.state==="idle"&&(this.state="running",a(this,'beforeSetup'),this.setup&&this.setup(),a(this,'afterSetup')),this.state==="running"&&(b=this.options.transition(b)*this.fromToDelta+this.options.from,this.position=b,a(this,'beforeUpdate'),this.update&&this.update(b),a(this,'afterUpdate'))}}(),this.event('beforeStart'),this.options.sync||Effect.Queues.get(Object.isString(this.options.queue)?'global':this.options.queue.scope).add(this)},loop:function(a){if(a>=this.startOn){if(a>=this.finishOn){this.render(1),this.cancel(),this.event('beforeFinish'),this.finish&&this.finish(),this.event('afterFinish');return}var b=(a-this.startOn)/this.totalTime,c=(b*this.totalFrames).round();c>this.currentFrame&&(this.render(b),this.currentFrame=c)}},cancel:function(){this.options.sync||Effect.Queues.get(Object.isString(this.options.queue)?'global':this.options.queue.scope).remove(this),this.state='finished'},event:function(a){this.options[a+'Internal']&&this.options[a+'Internal'](this),this.options[a]&&this.options[a](this)},inspect:function(){var a=$H();for(property in this)Object.isFunction(this[property])||a.set(property,this[property]);return'#<Effect:'+a.inspect()+',options:'+$H(this.options).inspect()+'>'}}),Effect.Parallel=Class.create(Effect.Base,{initialize:function(a){this.effects=a||[],this.start(arguments[1])},update:function(a){this.effects.invoke('render',a)},finish:function(a){this.effects.each(function(b){b.render(1),b.cancel(),b.event('beforeFinish'),b.finish&&b.finish(a),b.event('afterFinish')})}}),Effect.Tween=Class.create(Effect.Base,{initialize:function(a,d,e){a=Object.isString(a)?$(a):a;var c=$A(arguments),b=c.last(),f=c.length==5?c[3]:null;this.method=Object.isFunction(b)?b.bind(a):Object.isFunction(a[b])?a[b].bind(a):function(c){a[b]=c},this.start(Object.extend({from:d,to:e},f||{}))},update:function(a){this.method(a)}}),Effect.Event=Class.create(Effect.Base,{initialize:function(){this.start(Object.extend({duration:0},arguments[0]||{}))},update:Prototype.emptyFunction}),Effect.Opacity=Class.create(Effect.Base,{initialize:function(a){if(this.element=$(a),!this.element)throw Effect._elementDoesNotExistError;Prototype.Browser.IE&&!this.element.currentStyle.hasLayout&&this.element.setStyle({zoom:1});var b=Object.extend({from:this.element.getOpacity()||0,to:1},arguments[1]||{});this.start(b)},update:function(a){this.element.setOpacity(a)}}),Effect.Move=Class.create(Effect.Base,{initialize:function(a){if(this.element=$(a),!this.element)throw Effect._elementDoesNotExistError;var b=Object.extend({x:0,y:0,mode:'relative'},arguments[1]||{});this.start(b)},setup:function(){this.element.makePositioned(),this.originalLeft=parseFloat(this.element.getStyle('left')||'0'),this.originalTop=parseFloat(this.element.getStyle('top')||'0'),this.options.mode=='absolute'&&(this.options.x=this.options.x-this.originalLeft,this.options.y=this.options.y-this.originalTop)},update:function(a){this.element.setStyle({left:(this.options.x*a+this.originalLeft).round()+'px',top:(this.options.y*a+this.originalTop).round()+'px'})}}),Effect.MoveBy=function(a,b,c){return new Effect.Move(a,Object.extend({x:c,y:b},arguments[3]||{}))},Effect.Scale=Class.create(Effect.Base,{initialize:function(a,b){if(this.element=$(a),!this.element)throw Effect._elementDoesNotExistError;var c=Object.extend({scaleX:!0,scaleY:!0,scaleContent:!0,scaleFromCenter:!1,scaleMode:'box',scaleFrom:100,scaleTo:b},arguments[2]||{});this.start(c)},setup:function(){this.restoreAfterFinish=this.options.restoreAfterFinish||!1,this.elementPositioning=this.element.getStyle('position'),this.originalStyle={},['top','left','width','height','fontSize'].each(function(a){this.originalStyle[a]=this.element.style[a]}.bind(this)),this.originalTop=this.element.offsetTop,this.originalLeft=this.element.offsetLeft;var a=this.element.getStyle('font-size')||'100%';['em','px','%','pt'].each(function(b){a.indexOf(b)>0&&(this.fontSize=parseFloat(a),this.fontSizeType=b)}.bind(this)),this.factor=(this.options.scaleTo-this.options.scaleFrom)/100,this.dims=null,this.options.scaleMode=='box'&&(this.dims=[this.element.offsetHeight,this.element.offsetWidth]),/^content/.test(this.options.scaleMode)&&(this.dims=[this.element.scrollHeight,this.element.scrollWidth]),this.dims||(this.dims=[this.options.scaleMode.originalHeight,this.options.scaleMode.originalWidth])},update:function(b){var a=this.options.scaleFrom/100+this.factor*b;this.options.scaleContent&&this.fontSize&&this.element.setStyle({fontSize:this.fontSize*a+this.fontSizeType}),this.setDimensions(this.dims[0]*a,this.dims[1]*a)},finish:function(a){this.restoreAfterFinish&&this.element.setStyle(this.originalStyle)},setDimensions:function(b,c){var a={},d,e;this.options.scaleX&&(a.width=c.round()+'px'),this.options.scaleY&&(a.height=b.round()+'px'),this.options.scaleFromCenter&&(d=(b-this.dims[0])/2,e=(c-this.dims[1])/2,this.elementPositioning=='absolute'?(this.options.scaleY&&(a.top=this.originalTop-d+'px'),this.options.scaleX&&(a.left=this.originalLeft-e+'px')):(this.options.scaleY&&(a.top=-d+'px'),this.options.scaleX&&(a.left=-e+'px'))),this.element.setStyle(a)}}),Effect.Highlight=Class.create(Effect.Base,{initialize:function(a){if(this.element=$(a),!this.element)throw Effect._elementDoesNotExistError;var b=Object.extend({startcolor:'#ffff99'},arguments[1]||{});this.start(b)},setup:function(){if(this.element.getStyle('display')=='none'){this.cancel();return}this.oldStyle={},this.options.keepBackgroundImage||(this.oldStyle.backgroundImage=this.element.getStyle('background-image'),this.element.setStyle({backgroundImage:'none'})),this.options.endcolor||(this.options.endcolor=this.element.getStyle('background-color').parseColor('#ffffff')),this.options.restorecolor||(this.options.restorecolor=this.element.getStyle('background-color')),this._base=$R(0,2).map(function(a){return parseInt(this.options.startcolor.slice(a*2+1,a*2+3),16)}.bind(this)),this._delta=$R(0,2).map(function(a){return parseInt(this.options.endcolor.slice(a*2+1,a*2+3),16)-this._base[a]}.bind(this))},update:function(a){this.element.setStyle({backgroundColor:$R(0,2).inject('#',function(c,d,b){return c+(this._base[b]+this._delta[b]*a).round().toColorPart()}.bind(this))})},finish:function(){this.element.setStyle(Object.extend(this.oldStyle,{backgroundColor:this.options.restorecolor}))}}),Effect.ScrollTo=function(d){var a=arguments[1]||{},b=document.viewport.getScrollOffsets(),c=$(d).cumulativeOffset();return a.offset&&(c[1]+=a.offset),new Effect.Tween(null,b.top,c[1],a,function(a){scrollTo(b.left,a.round())})},Effect.Fade=function(a){var b,c;return a=$(a),b=a.getInlineOpacity(),c=Object.extend({from:a.getOpacity()||1,to:0,afterFinishInternal:function(a){if(a.options.to!=0)return;a.element.hide().setStyle({opacity:b})}},arguments[1]||{}),new Effect.Opacity(a,c)},Effect.Appear=function(a){a=$(a);var b=Object.extend({from:a.getStyle('display')=='none'?0:a.getOpacity()||0,to:1,afterFinishInternal:function(a){a.element.forceRerendering()},beforeSetup:function(a){a.element.setOpacity(a.options.from).show()}},arguments[1]||{});return new Effect.Opacity(a,b)},Effect.Puff=function(a){a=$(a);var b={opacity:a.getInlineOpacity(),position:a.getStyle('position'),top:a.style.top,left:a.style.left,width:a.style.width,height:a.style.height};return new Effect.Parallel([new Effect.Scale(a,200,{sync:!0,scaleFromCenter:!0,scaleContent:!0,restoreAfterFinish:!0}),new Effect.Opacity(a,{sync:!0,to:0})],Object.extend({duration:1,beforeSetupInternal:function(a){Position.absolutize(a.effects[0].element)},afterFinishInternal:function(a){a.effects[0].element.hide().setStyle(b)}},arguments[1]||{}))},Effect.BlindUp=function(a){return a=$(a),a.makeClipping(),new Effect.Scale(a,0,Object.extend({scaleContent:!1,scaleX:!1,restoreAfterFinish:!0,afterFinishInternal:function(a){a.element.hide().undoClipping()}},arguments[1]||{}))},Effect.BlindDown=function(a){a=$(a);var b=a.getDimensions();return new Effect.Scale(a,100,Object.extend({scaleContent:!1,scaleX:!1,scaleFrom:0,scaleMode:{originalHeight:b.height,originalWidth:b.width},restoreAfterFinish:!0,afterSetup:function(a){a.element.makeClipping().setStyle({height:'0px'}).show()},afterFinishInternal:function(a){a.element.undoClipping()}},arguments[1]||{}))},Effect.SwitchOff=function(a){a=$(a);var b=a.getInlineOpacity();return new Effect.Appear(a,Object.extend({duration:.4,from:0,transition:Effect.Transitions.flicker,afterFinishInternal:function(a){new Effect.Scale(a.element,1,{duration:.3,scaleFromCenter:!0,scaleX:!1,scaleContent:!1,restoreAfterFinish:!0,beforeSetup:function(a){a.element.makePositioned().makeClipping()},afterFinishInternal:function(a){a.element.hide().undoClipping().undoPositioned().setStyle({opacity:b})}})}},arguments[1]||{}))},Effect.DropOut=function(a){a=$(a);var b={top:a.getStyle('top'),left:a.getStyle('left'),opacity:a.getInlineOpacity()};return new Effect.Parallel([new Effect.Move(a,{x:0,y:100,sync:!0}),new Effect.Opacity(a,{sync:!0,to:0})],Object.extend({duration:.5,beforeSetup:function(a){a.effects[0].element.makePositioned()},afterFinishInternal:function(a){a.effects[0].element.hide().undoPositioned().setStyle(b)}},arguments[1]||{}))},Effect.Shake=function(c){var d,a,b,e;return c=$(c),d=Object.extend({distance:20,duration:.5},arguments[1]||{}),a=parseFloat(d.distance),b=parseFloat(d.duration)/10,e={top:c.getStyle('top'),left:c.getStyle('left')},new Effect.Move(c,{x:a,y:0,duration:b,afterFinishInternal:function(c){new Effect.Move(c.element,{x:-a*2,y:0,duration:b*2,afterFinishInternal:function(c){new Effect.Move(c.element,{x:a*2,y:0,duration:b*2,afterFinishInternal:function(c){new Effect.Move(c.element,{x:-a*2,y:0,duration:b*2,afterFinishInternal:function(c){new Effect.Move(c.element,{x:a*2,y:0,duration:b*2,afterFinishInternal:function(c){new Effect.Move(c.element,{x:-a,y:0,duration:b,afterFinishInternal:function(a){a.element.undoPositioned().setStyle(e)}})}})}})}})}})}})},Effect.SlideDown=function(a){var c,b;return a=$(a).cleanWhitespace(),c=a.down().getStyle('bottom'),b=a.getDimensions(),new Effect.Scale(a,100,Object.extend({scaleContent:!1,scaleX:!1,scaleFrom:window.opera?0:1,scaleMode:{originalHeight:b.height,originalWidth:b.width},restoreAfterFinish:!0,afterSetup:function(a){a.element.makePositioned(),a.element.down().makePositioned(),window.opera&&a.element.setStyle({top:''}),a.element.makeClipping().setStyle({height:'0px'}).show()},afterUpdateInternal:function(a){a.element.down().setStyle({bottom:a.dims[0]-a.element.clientHeight+'px'})},afterFinishInternal:function(a){a.element.undoClipping().undoPositioned(),a.element.down().undoPositioned().setStyle({bottom:c})}},arguments[1]||{}))},Effect.SlideUp=function(a){var c,b;return a=$(a).cleanWhitespace(),c=a.down().getStyle('bottom'),b=a.getDimensions(),new Effect.Scale(a,window.opera?0:1,Object.extend({scaleContent:!1,scaleX:!1,scaleMode:'box',scaleFrom:100,scaleMode:{originalHeight:b.height,originalWidth:b.width},restoreAfterFinish:!0,afterSetup:function(a){a.element.makePositioned(),a.element.down().makePositioned(),window.opera&&a.element.setStyle({top:''}),a.element.makeClipping().show()},afterUpdateInternal:function(a){a.element.down().setStyle({bottom:a.dims[0]-a.element.clientHeight+'px'})},afterFinishInternal:function(a){a.element.hide().undoClipping().undoPositioned(),a.element.down().undoPositioned().setStyle({bottom:c})}},arguments[1]||{}))},Effect.Squish=function(a){return new Effect.Scale(a,window.opera?1:0,{restoreAfterFinish:!0,beforeSetup:function(a){a.element.makeClipping()},afterFinishInternal:function(a){a.element.hide().undoClipping()}})},Effect.Grow=function(b){var g,h,a,d,e,f,c;switch(b=$(b),g=Object.extend({direction:'center',moveTransition:Effect.Transitions.sinoidal,scaleTransition:Effect.Transitions.sinoidal,opacityTransition:Effect.Transitions.full},arguments[1]||{}),h={top:b.style.top,left:b.style.left,height:b.style.height,width:b.style.width,opacity:b.getInlineOpacity()},a=b.getDimensions(),g.direction){case'top-left':d=e=f=c=0;break;case'top-right':d=a.width,e=c=0,f=-a.width;break;case'bottom-left':d=f=0,e=a.height,c=-a.height;break;case'bottom-right':d=a.width,e=a.height,f=-a.width,c=-a.height;break;case'center':d=a.width/2,e=a.height/2,f=-a.width/2,c=-a.height/2;break}return new Effect.Move(b,{x:d,y:e,duration:.01,beforeSetup:function(a){a.element.hide().makeClipping().makePositioned()},afterFinishInternal:function(b){new Effect.Parallel([new Effect.Opacity(b.element,{sync:!0,to:1,from:0,transition:g.opacityTransition}),new Effect.Move(b.element,{x:f,y:c,sync:!0,transition:g.moveTransition}),new Effect.Scale(b.element,100,{scaleMode:{originalHeight:a.height,originalWidth:a.width},sync:!0,scaleFrom:window.opera?1:0,transition:g.scaleTransition,restoreAfterFinish:!0})],Object.extend({beforeSetup:function(a){a.effects[0].element.setStyle({height:'0px'}).show()},afterFinishInternal:function(a){a.effects[0].element.undoClipping().undoPositioned().setStyle(h)}},g))}})},Effect.Shrink=function(a){var e,f,b,c,d;switch(a=$(a),e=Object.extend({direction:'center',moveTransition:Effect.Transitions.sinoidal,scaleTransition:Effect.Transitions.sinoidal,opacityTransition:Effect.Transitions.none},arguments[1]||{}),f={top:a.style.top,left:a.style.left,height:a.style.height,width:a.style.width,opacity:a.getInlineOpacity()},b=a.getDimensions(),e.direction){case'top-left':c=d=0;break;case'top-right':c=b.width,d=0;break;case'bottom-left':c=0,d=b.height;break;case'bottom-right':c=b.width,d=b.height;break;case'center':c=b.width/2,d=b.height/2;break}return new Effect.Parallel([new Effect.Opacity(a,{sync:!0,to:0,from:1,transition:e.opacityTransition}),new Effect.Scale(a,window.opera?1:0,{sync:!0,transition:e.scaleTransition,restoreAfterFinish:!0}),new Effect.Move(a,{x:c,y:d,sync:!0,transition:e.moveTransition})],Object.extend({beforeStartInternal:function(a){a.effects[0].element.makePositioned().makeClipping()},afterFinishInternal:function(a){a.effects[0].element.hide().undoClipping().undoPositioned().setStyle(f)}},e))},Effect.Pulsate=function(a){a=$(a);var b=arguments[1]||{},c=a.getInlineOpacity(),d=b.transition||Effect.Transitions.linear,e=function(a){return 1-d(-Math.cos(a*(b.pulses||5)*2*Math.PI)/2+.5)};return new Effect.Opacity(a,Object.extend(Object.extend({duration:2,from:0,afterFinishInternal:function(a){a.element.setStyle({opacity:c})}},b),{transition:e}))},Effect.Fold=function(a){a=$(a);var b={top:a.style.top,left:a.style.left,width:a.style.width,height:a.style.height};return a.makeClipping(),new Effect.Scale(a,5,Object.extend({scaleContent:!1,scaleX:!1,afterFinishInternal:function(c){new Effect.Scale(a,1,{scaleContent:!1,scaleY:!1,afterFinishInternal:function(a){a.element.hide().undoClipping().setStyle(b)}})}},arguments[1]||{}))},Effect.Morph=Class.create(Effect.Base,{initialize:function(c){var a,b;if(this.element=$(c),!this.element)throw Effect._elementDoesNotExistError;a=Object.extend({style:{}},arguments[1]||{}),Object.isString(a.style)?a.style.include(':')?this.style=a.style.parseStyle():(this.element.addClassName(a.style),this.style=$H(this.element.getStyles()),this.element.removeClassName(a.style),b=this.element.getStyles(),this.style=this.style.reject(function(a){return a.value==b[a.key]}),a.afterFinishInternal=function(a){a.element.addClassName(a.options.style),a.transforms.each(function(b){a.element.style[b.style]=''})}):this.style=$H(a.style),this.start(a)},setup:function(){function a(a){return(!a||['rgba(0, 0, 0, 0)','transparent'].include(a))&&(a='#ffffff'),a=a.parseColor(),$R(0,2).map(function(b){return parseInt(a.slice(b*2+1,b*2+3),16)})}this.transforms=this.style.map(function(f){var d=f[0],b=f[1],c=null,e,g;return b.parseColor('#zzzzzz')!='#zzzzzz'?(b=b.parseColor(),c='color'):d=='opacity'?(b=parseFloat(b),Prototype.Browser.IE&&!this.element.currentStyle.hasLayout&&this.element.setStyle({zoom:1})):Element.CSS_LENGTH.test(b)&&(e=b.match(/^([\+\-]?[0-9\.]+)(.*)$/),b=parseFloat(e[1]),c=e.length==3?e[2]:null),g=this.element.getStyle(d),{style:d.camelize(),originalValue:c=='color'?a(g):parseFloat(g||0),targetValue:c=='color'?a(b):b,unit:c}}.bind(this)).reject(function(a){return a.originalValue==a.targetValue||a.unit!='color'&&(isNaN(a.originalValue)||isNaN(a.targetValue))})},update:function(b){for(var c={},a,d=this.transforms.length;d--;)c[(a=this.transforms[d]).style]=a.unit=='color'?'#'+Math.round(a.originalValue[0]+(a.targetValue[0]-a.originalValue[0])*b).toColorPart()+Math.round(a.originalValue[1]+(a.targetValue[1]-a.originalValue[1])*b).toColorPart()+Math.round(a.originalValue[2]+(a.targetValue[2]-a.originalValue[2])*b).toColorPart():(a.originalValue+(a.targetValue-a.originalValue)*b).toFixed(3)+(a.unit===null?'':a.unit);this.element.setStyle(c,!0)}}),Effect.Transform=Class.create({initialize:function(a){this.tracks=[],this.options=arguments[1]||{},this.addTracks(a)},addTracks:function(a){return a.each(function(a){a=$H(a);var b=a.values().first();this.tracks.push($H({ids:a.keys().first(),effect:Effect.Morph,options:{style:b}}))}.bind(this)),this},play:function(){return new Effect.Parallel(this.tracks.map(function(a){var b=a.get('ids'),c=a.get('effect'),d=a.get('options'),e=[$(b)||$$(b)].flatten();return e.map(function(a){return new c(a,Object.extend({sync:!0},d))})}).flatten(),this.options)}}),Element.CSS_PROPERTIES=$w('backgroundColor backgroundPosition borderBottomColor borderBottomStyle borderBottomWidth borderLeftColor borderLeftStyle borderLeftWidth borderRightColor borderRightStyle borderRightWidth borderSpacing borderTopColor borderTopStyle borderTopWidth bottom clip color fontSize fontWeight height left letterSpacing lineHeight marginBottom marginLeft marginRight marginTop markerOffset maxHeight maxWidth minHeight minWidth opacity outlineColor outlineOffset outlineWidth paddingBottom paddingLeft paddingRight paddingTop right textIndent top width wordSpacing zIndex'),Element.CSS_LENGTH=/^(([\+\-]?[0-9\.]+)(em|ex|px|in|cm|mm|pt|pc|\%))|0$/,String.__parseStyleElement=document.createElement('div'),String.prototype.parseStyle=function(){var a,b=$H();return Prototype.Browser.WebKit?a=new Element('div',{style:this}).style:(String.__parseStyleElement.innerHTML='<div style="'+this+'"></div>',a=String.__parseStyleElement.childNodes[0].style),Element.CSS_PROPERTIES.each(function(c){a[c]&&b.set(c,a[c])}),Prototype.Browser.IE&&this.include('opacity')&&b.set('opacity',this.match(/opacity:\s*((?:0|1)?(?:\.\d*)?)/)[1]),b},document.defaultView&&document.defaultView.getComputedStyle?Element.getStyles=function(a){var b=document.defaultView.getComputedStyle($(a),null);return Element.CSS_PROPERTIES.inject({},function(a,c){return a[c]=b[c],a})}:Element.getStyles=function(a){a=$(a);var c=a.currentStyle,b;return b=Element.CSS_PROPERTIES.inject({},function(a,b){return a[b]=c[b],a}),b.opacity||(b.opacity=a.getOpacity()),b},Effect.Methods={morph:function(a,b){return a=$(a),new Effect.Morph(a,Object.extend({style:b},arguments[2]||{})),a},visualEffect:function(a,c,d){a=$(a);var b=c.dasherize().camelize(),e=b.charAt(0).toUpperCase()+b.substring(1);return new Effect[e](a,d),a},highlight:function(a,b){return a=$(a),new Effect.Highlight(a,b),a}},$w('fade appear grow shrink fold blindUp blindDown slideUp slideDown pulsate shake puff squish switchOff dropOut').each(function(a){Effect.Methods[a]=function(b,c){return b=$(b),Effect[a.charAt(0).toUpperCase()+a.substring(1)](b,c),b}}),$w('getInlineOpacity forceRerendering setContentZoom collectTextNodes collectTextNodesIgnoreClass getStyles').each(function(a){Effect.Methods[a]=Element[a]}),Element.addMethods(Effect.Methods),Object.isUndefined(Effect))throw"dragdrop.js requires including script.aculo.us' effects.js library";if(Droppables={drops:[],remove:function(a){this.drops=this.drops.reject(function(b){return b.element==$(a)})},add:function(b){var a,c;b=$(b),a=Object.extend({greedy:!0,hoverclass:null,tree:!1},arguments[1]||{}),a.containment&&(a._containers=[],c=a.containment,Object.isArray(c)?c.each(function(b){a._containers.push($(b))}):a._containers.push($(c))),a.accept&&(a.accept=[a.accept].flatten()),Element.makePositioned(b),a.element=b,this.drops.push(a)},findDeepestChild:function(a){deepest=a[0];for(i=1;i<a.length;++i)Element.isParent(a[i].element,deepest.element)&&(deepest=a[i]);return deepest},isContained:function(b,c){var a;return c.tree?a=b.treeNode:a=b.parentNode,c._containers.detect(function(b){return a==b})},isAffected:function(c,b,a){return a.element!=b&&(!a._containers||this.isContained(b,a))&&(!a.accept||Element.classNames(b).detect(function(b){return a.accept.include(b)}))&&Position.within(a.element,c[0],c[1])},deactivate:function(a){a.hoverclass&&Element.removeClassName(a.element,a.hoverclass),this.last_active=null},activate:function(a){a.hoverclass&&Element.addClassName(a.element,a.hoverclass),this.last_active=a},show:function(b,d){if(!this.drops.length)return;var a,c=[];this.drops.each(function(a){Droppables.isAffected(b,d,a)&&c.push(a)}),c.length>0&&(a=Droppables.findDeepestChild(c)),this.last_active&&this.last_active!=a&&this.deactivate(this.last_active),a&&(Position.within(a.element,b[0],b[1]),a.onHover&&a.onHover(d,a.element,Position.overlap(a.overlap,a.element)),a!=this.last_active&&Droppables.activate(a))},fire:function(a,b){if(!this.last_active)return;if(Position.prepare(),this.isAffected([Event.pointerX(a),Event.pointerY(a)],b,this.last_active)){if(this.last_active.onDrop)return this.last_active.onDrop(b,this.last_active.element,a),!0}},reset:function(){this.last_active&&this.deactivate(this.last_active)}},Draggables={drags:[],observers:[],register:function(a){this.drags.length==0&&(this.eventMouseUp=this.endDrag.bindAsEventListener(this),this.eventMouseMove=this.updateDrag.bindAsEventListener(this),this.eventKeypress=this.keyPress.bindAsEventListener(this),Event.observe(document,"mouseup",this.eventMouseUp),Event.observe(document,"mousemove",this.eventMouseMove),Event.observe(document,"keypress",this.eventKeypress)),this.drags.push(a)},unregister:function(a){this.drags=this.drags.reject(function(b){return b==a}),this.drags.length==0&&(Event.stopObserving(document,"mouseup",this.eventMouseUp),Event.stopObserving(document,"mousemove",this.eventMouseMove),Event.stopObserving(document,"keypress",this.eventKeypress))},activate:function(a){a.options.delay?this._timeout=setTimeout(function(){Draggables._timeout=null,window.focus(),Draggables.activeDraggable=a}.bind(this),a.options.delay):(window.focus(),this.activeDraggable=a)},deactivate:function(){this.activeDraggable=null},updateDrag:function(a){if(!this.activeDraggable)return;var b=[Event.pointerX(a),Event.pointerY(a)];if(this._lastPointer&&this._lastPointer.inspect()==b.inspect())return;this._lastPointer=b,this.activeDraggable.updateDrag(a,b)},endDrag:function(a){if(this._timeout&&(clearTimeout(this._timeout),this._timeout=null),!this.activeDraggable)return;this._lastPointer=null,this.activeDraggable.endDrag(a),this.activeDraggable=null},keyPress:function(a){this.activeDraggable&&this.activeDraggable.keyPress(a)},addObserver:function(a){this.observers.push(a),this._cacheObserverCallbacks()},removeObserver:function(a){this.observers=this.observers.reject(function(b){return b.element==a}),this._cacheObserverCallbacks()},notify:function(a,b,c){this[a+'Count']>0&&this.observers.each(function(d){d[a]&&d[a](a,b,c)}),b.options[a]&&b.options[a](b,c)},_cacheObserverCallbacks:function(){['onStart','onEnd','onDrag'].each(function(a){Draggables[a+'Count']=Draggables.observers.select(function(b){return b[a]}).length})}},Draggable=Class.create({initialize:function(c){var b={handle:!1,reverteffect:function(c,a,b){var d=Math.sqrt(Math.abs(a^2)+Math.abs(b^2))*.02;new Effect.Move(c,{x:-b,y:-a,duration:d,queue:{scope:'_draggable',position:'end'}})},endeffect:function(a){var b=Object.isNumber(a._opacity)?a._opacity:1;new Effect.Opacity(a,{duration:.2,from:.7,to:b,queue:{scope:'_draggable',position:'end'},afterFinish:function(){Draggable._dragging[a]=!1}})},zindex:1e3,revert:!1,quiet:!1,scroll:!1,scrollSensitivity:20,scrollSpeed:15,snap:!1,delay:0},a;(!arguments[1]||Object.isUndefined(arguments[1].endeffect))&&Object.extend(b,{starteffect:function(a){a._opacity=Element.getOpacity(a),Draggable._dragging[a]=!0,new Effect.Opacity(a,{duration:.2,from:a._opacity,to:.7})}}),a=Object.extend(b,arguments[1]||{}),this.element=$(c),a.handle&&Object.isString(a.handle)&&(this.handle=this.element.down('.'+a.handle,0)),this.handle||(this.handle=$(a.handle)),this.handle||(this.handle=this.element),a.scroll&&!a.scroll.scrollTo&&!a.scroll.outerHTML&&(a.scroll=$(a.scroll),this._isScrollChild=Element.childOf(this.element,a.scroll)),Element.makePositioned(this.element),this.options=a,this.dragging=!1,this.eventMouseDown=this.initDrag.bindAsEventListener(this),Event.observe(this.handle,"mousedown",this.eventMouseDown),Draggables.register(this)},destroy:function(){Event.stopObserving(this.handle,"mousedown",this.eventMouseDown),Draggables.unregister(this)},currentDelta:function(){return[parseInt(Element.getStyle(this.element,'left')||'0'),parseInt(Element.getStyle(this.element,'top')||'0')]},initDrag:function(a){var b,c,d;if(!Object.isUndefined(Draggable._dragging[this.element])&&Draggable._dragging[this.element])return;if(Event.isLeftClick(a)){if(b=Event.element(a),(tag_name=b.tagName.toUpperCase())&&(tag_name=='INPUT'||tag_name=='SELECT'||tag_name=='OPTION'||tag_name=='BUTTON'||tag_name=='TEXTAREA'))return;c=[Event.pointerX(a),Event.pointerY(a)],d=this.element.cumulativeOffset(),this.offset=[0,1].map(function(a){return c[a]-d[a]}),Draggables.activate(this),Event.stop(a)}},startDrag:function(b){if(this.dragging=!0,this.delta||(this.delta=this.currentDelta()),this.options.zindex&&(this.originalZ=parseInt(Element.getStyle(this.element,'z-index')||0),this.element.style.zIndex=this.options.zindex),this.options.ghosting&&(this._clone=this.element.cloneNode(!0),this._originallyAbsolute=this.element.getStyle('position')=='absolute',this._originallyAbsolute||Position.absolutize(this.element),this.element.parentNode.insertBefore(this._clone,this.element)),this.options.scroll)if(this.options.scroll==window){var a=this._getWindowScroll(this.options.scroll);this.originalScrollLeft=a.left,this.originalScrollTop=a.top}else this.originalScrollLeft=this.options.scroll.scrollLeft,this.originalScrollTop=this.options.scroll.scrollTop;Draggables.notify('onStart',this,b),this.options.starteffect&&this.options.starteffect(this.element)},updateDrag:function(event,pointer){var p,speed;if(this.dragging||this.startDrag(event),this.options.quiet||(Position.prepare(),Droppables.show(pointer,this.element)),Draggables.notify('onDrag',this,event),this.draw(pointer),this.options.change&&this.options.change(this),this.options.scroll){if(this.stopScrolling(),this.options.scroll==window)with(this._getWindowScroll(this.options.scroll))p=[left,top,left+width,top+height];else p=Position.page(this.options.scroll).toArray(),p[0]+=this.options.scroll.scrollLeft+Position.deltaX,p[1]+=this.options.scroll.scrollTop+Position.deltaY,p.push(p[0]+this.options.scroll.offsetWidth),p.push(p[1]+this.options.scroll.offsetHeight);speed=[0,0],pointer[0]<p[0]+this.options.scrollSensitivity&&(speed[0]=pointer[0]-(p[0]+this.options.scrollSensitivity)),pointer[1]<p[1]+this.options.scrollSensitivity&&(speed[1]=pointer[1]-(p[1]+this.options.scrollSensitivity)),pointer[0]>p[2]-this.options.scrollSensitivity&&(speed[0]=pointer[0]-(p[2]-this.options.scrollSensitivity)),pointer[1]>p[3]-this.options.scrollSensitivity&&(speed[1]=pointer[1]-(p[3]-this.options.scrollSensitivity)),this.startScrolling(speed)}Prototype.Browser.WebKit&&window.scrollBy(0,0),Event.stop(event)},finishDrag:function(c,f){var e,b,a,d;this.dragging=!1,this.options.quiet&&(Position.prepare(),e=[Event.pointerX(c),Event.pointerY(c)],Droppables.show(e,this.element)),this.options.ghosting&&(this._originallyAbsolute||Position.relativize(this.element),delete this._originallyAbsolute,Element.remove(this._clone),this._clone=null),b=!1,f&&(b=Droppables.fire(c,this.element),b||(b=!1)),b&&this.options.onDropped&&this.options.onDropped(this.element),Draggables.notify('onEnd',this,c),a=this.options.revert,a&&Object.isFunction(a)&&(a=a(this.element)),d=this.currentDelta(),a&&this.options.reverteffect?(b==0||a!='failure')&&this.options.reverteffect(this.element,d[1]-this.delta[1],d[0]-this.delta[0]):this.delta=d,this.options.zindex&&(this.element.style.zIndex=this.originalZ),this.options.endeffect&&this.options.endeffect(this.element),Draggables.deactivate(this),Droppables.reset()},keyPress:function(a){if(a.keyCode!=Event.KEY_ESC)return;this.finishDrag(a,!1),Event.stop(a)},endDrag:function(a){if(!this.dragging)return;this.stopScrolling(),this.finishDrag(a,!0),Event.stop(a)},draw:function(f){var b=this.element.cumulativeOffset(),d,e,a,c;this.options.ghosting&&(d=Position.realOffset(this.element),b[0]+=d[0]-Position.deltaX,b[1]+=d[1]-Position.deltaY),e=this.currentDelta(),b[0]-=e[0],b[1]-=e[1],this.options.scroll&&this.options.scroll!=window&&this._isScrollChild&&(b[0]-=this.options.scroll.scrollLeft-this.originalScrollLeft,b[1]-=this.options.scroll.scrollTop-this.originalScrollTop),a=[0,1].map(function(a){return f[a]-b[a]-this.offset[a]}.bind(this)),this.options.snap&&(Object.isFunction(this.options.snap)?a=this.options.snap(a[0],a[1],this):Object.isArray(this.options.snap)?a=a.map(function(b,a){return(b/this.options.snap[a]).round()*this.options.snap[a]}.bind(this)):a=a.map(function(a){return(a/this.options.snap).round()*this.options.snap}.bind(this))),c=this.element.style,(!this.options.constraint||this.options.constraint=='horizontal')&&(c.left=a[0]+"px"),(!this.options.constraint||this.options.constraint=='vertical')&&(c.top=a[1]+"px"),c.visibility=="hidden"&&(c.visibility="")},stopScrolling:function(){this.scrollInterval&&(clearInterval(this.scrollInterval),this.scrollInterval=null,Draggables._lastScrollPointer=null)},startScrolling:function(a){if(!(a[0]||a[1]))return;this.scrollSpeed=[a[0]*this.options.scrollSpeed,a[1]*this.options.scrollSpeed],this.lastScrolled=new Date,this.scrollInterval=setInterval(this.scroll.bind(this),10)},scroll:function(){var current=new Date,delta=current-this.lastScrolled,d;if(this.lastScrolled=current,this.options.scroll==window){with(this._getWindowScroll(this.options.scroll))(this.scrollSpeed[0]||this.scrollSpeed[1])&&(d=delta/1e3,this.options.scroll.scrollTo(left+d*this.scrollSpeed[0],top+d*this.scrollSpeed[1]))}else this.options.scroll.scrollLeft+=this.scrollSpeed[0]*delta/1e3,this.options.scroll.scrollTop+=this.scrollSpeed[1]*delta/1e3;Position.prepare(),Droppables.show(Draggables._lastPointer,this.element),Draggables.notify('onDrag',this),this._isScrollChild&&(Draggables._lastScrollPointer=Draggables._lastScrollPointer||$A(Draggables._lastPointer),Draggables._lastScrollPointer[0]+=this.scrollSpeed[0]*delta/1e3,Draggables._lastScrollPointer[1]+=this.scrollSpeed[1]*delta/1e3,Draggables._lastScrollPointer[0]<0&&(Draggables._lastScrollPointer[0]=0),Draggables._lastScrollPointer[1]<0&&(Draggables._lastScrollPointer[1]=0),this.draw(Draggables._lastScrollPointer)),this.options.change&&this.options.change(this)},_getWindowScroll:function(w){var T,L,W,H;with(w.document)w.document.documentElement&&documentElement.scrollTop?(T=documentElement.scrollTop,L=documentElement.scrollLeft):w.document.body&&(T=body.scrollTop,L=body.scrollLeft),w.innerWidth?(W=w.innerWidth,H=w.innerHeight):w.document.documentElement&&documentElement.clientWidth?(W=documentElement.clientWidth,H=documentElement.clientHeight):(W=body.offsetWidth,H=body.offsetHeight);return{top:T,left:L,width:W,height:H}}}),Draggable._dragging={},SortableObserver=Class.create({initialize:function(a,b){this.element=$(a),this.observer=b,this.lastValue=Sortable.serialize(this.element)},onStart:function(){this.lastValue=Sortable.serialize(this.element)},onEnd:function(){Sortable.unmark(),this.lastValue!=Sortable.serialize(this.element)&&this.observer(this.element)}}),Sortable={SERIALIZE_RULE:/^[^_\-](?:[A-Za-z0-9\-\_]*)[_](.*)$/,sortables:{},_findRootElement:function(a){while(a.tagName.toUpperCase()!="BODY"){if(a.id&&Sortable.sortables[a.id])return a;a=a.parentNode}},options:function(a){if(a=Sortable._findRootElement($(a)),!a)return;return Sortable.sortables[a.id]},destroy:function(b){b=$(b);var a=Sortable.sortables[b.id];a&&(Draggables.removeObserver(a.element),a.droppables.each(function(a){Droppables.remove(a)}),a.draggables.invoke('destroy'),delete Sortable.sortables[a.element.id])},create:function(b){var a,c,e,d;b=$(b),a=Object.extend({element:b,tag:'li',dropOnEmpty:!1,tree:!1,treeTag:'ul',overlap:'vertical',constraint:'vertical',containment:b,handle:!1,only:!1,delay:0,hoverclass:null,ghosting:!1,quiet:!1,scroll:!1,scrollSensitivity:20,scrollSpeed:15,format:this.SERIALIZE_RULE,elements:!1,handles:!1,onChange:Prototype.emptyFunction,onUpdate:Prototype.emptyFunction},arguments[1]||{}),this.destroy(b),c={revert:!0,quiet:a.quiet,scroll:a.scroll,scrollSpeed:a.scrollSpeed,scrollSensitivity:a.scrollSensitivity,delay:a.delay,ghosting:a.ghosting,constraint:a.constraint,handle:a.handle},a.starteffect&&(c.starteffect=a.starteffect),a.reverteffect?c.reverteffect=a.reverteffect:a.ghosting&&(c.reverteffect=function(a){a.style.top=0,a.style.left=0}),a.endeffect&&(c.endeffect=a.endeffect),a.zindex&&(c.zindex=a.zindex),e={overlap:a.overlap,containment:a.containment,tree:a.tree,hoverclass:a.hoverclass,onHover:Sortable.onHover},d={onHover:Sortable.onEmptyHover,overlap:a.overlap,containment:a.containment,hoverclass:a.hoverclass},Element.cleanWhitespace(b),a.draggables=[],a.droppables=[],(a.dropOnEmpty||a.tree)&&(Droppables.add(b,d),a.droppables.push(b)),(a.elements||this.findElements(b,a)||[]).each(function(d,f){var g=a.handles?$(a.handles[f]):a.handle?$(d).select('.'+a.handle)[0]:d;a.draggables.push(new Draggable(d,Object.extend(c,{handle:g}))),Droppables.add(d,e),a.tree&&(d.treeNode=b),a.droppables.push(d)}),a.tree&&(Sortable.findTreeElements(b,a)||[]).each(function(c){Droppables.add(c,d),c.treeNode=b,a.droppables.push(c)}),this.sortables[b.identify()]=a,Draggables.addObserver(new SortableObserver(b,a.onUpdate))},findElements:function(b,a){return Element.findChildren(b,a.only,!!a.tree,a.tag)},findTreeElements:function(b,a){return Element.findChildren(b,a.only,!!a.tree,a.treeTag)},onHover:function(a,b,d){var c,e;if(Element.isParent(b,a))return;if(d>.33&&d<.66&&Sortable.options(b).tree)return;d>.5?(Sortable.mark(b,'before'),b.previousSibling!=a&&(c=a.parentNode,a.style.visibility="hidden",b.parentNode.insertBefore(a,b),b.parentNode!=c&&Sortable.options(c).onChange(a),Sortable.options(b.parentNode).onChange(a))):(Sortable.mark(b,'after'),e=b.nextSibling||null,e!=a&&(c=a.parentNode,a.style.visibility="hidden",b.parentNode.insertBefore(a,e),b.parentNode!=c&&Sortable.options(c).onChange(a),Sortable.options(b.parentNode).onChange(a)))},onEmptyHover:function(e,d,i){var h=e.parentNode,c=Sortable.options(d),a,b,g,f;if(!Element.isParent(d,e)){if(b=Sortable.findElements(d,{tag:c.tag,only:c.only}),g=null,b){f=Element.offsetSize(d,c.overlap)*(1-i);for(a=0;a<b.length;a+=1)if(f-Element.offsetSize(b[a],c.overlap)>=0)f-=Element.offsetSize(b[a],c.overlap);else if(f-Element.offsetSize(b[a],c.overlap)/2>=0){g=a+1<b.length?b[a+1]:null;break}else{g=b[a];break}}d.insertBefore(e,g),Sortable.options(h).onChange(e),c.onChange(e)}},unmark:function(){Sortable._marker&&Sortable._marker.hide()},mark:function(a,d){var c=Sortable.options(a.parentNode),b;if(c&&!c.ghosting)return;Sortable._marker||(Sortable._marker=($('dropmarker')||Element.extend(document.createElement('DIV'))).hide().addClassName('dropmarker').setStyle({position:'absolute'}),document.getElementsByTagName("body").item(0).appendChild(Sortable._marker)),b=a.cumulativeOffset(),Sortable._marker.setStyle({left:b[0]+'px',top:b[1]+'px'}),d=='after'&&(c.overlap=='horizontal'?Sortable._marker.setStyle({left:b[0]+a.clientWidth+'px'}):Sortable._marker.setStyle({top:b[1]+a.clientHeight+'px'})),Sortable._marker.show()},_tree:function(g,b,c){for(var e=Sortable.findElements(g,b)||[],d=0,f,a;d<e.length;++d){if(f=e[d].id.match(b.format),!f)continue;a={id:encodeURIComponent(f?f[1]:null),element:g,parent:c,children:[],position:c.children.length,container:$(e[d]).down(b.treeTag)},a.container&&this._tree(a.container,b,a),c.children.push(a)}return c},tree:function(a){var b,c,d;return a=$(a),b=this.options(a),c=Object.extend({tag:b.tag,treeTag:b.treeTag,only:b.only,name:a.id,format:b.format},arguments[1]||{}),d={id:null,parent:null,children:[],container:a,position:0},Sortable._tree(a,c,d)},_constructIndex:function(a){var b='';do a.id&&(b='['+a.position+']'+b);while((a=a.parent)!=null)return b},sequence:function(a){a=$(a);var b=Object.extend(this.options(a),arguments[1]||{});return $(this.findElements(a,b)||[]).map(function(a){return a.id.match(b.format)?a.id.match(b.format)[1]:''})},setSequence:function(a,d){var b,c;a=$(a),b=Object.extend(this.options(a),arguments[2]||{}),c={},this.findElements(a,b).each(function(a){a.id.match(b.format)&&(c[a.id.match(b.format)[1]]=[a,a.parentNode]),a.parentNode.removeChild(a)}),d.each(function(b){var a=c[b];a&&(a[1].appendChild(a[0]),delete c[b])})},serialize:function(a){var c,b;return a=$(a),c=Object.extend(Sortable.options(a),arguments[1]||{}),b=encodeURIComponent(arguments[1]&&arguments[1].name?arguments[1].name:a.id),c.tree?Sortable.tree(a,arguments[1]).children.map(function(a){return[b+Sortable._constructIndex(a)+"[id]="+encodeURIComponent(a.id)].concat(a.children.map(arguments.callee))}).flatten().join('&'):Sortable.sequence(a,arguments[1]).map(function(a){return b+"[]="+encodeURIComponent(a)}).join('&')}},Element.isParent=function(a,b){return!(!a.parentNode||a==b)&&(a.parentNode==b||Element.isParent(a.parentNode,b))},Element.findChildren=function(d,a,e,b){if(!d.hasChildNodes())return null;b=b.toUpperCase(),a&&(a=[a].flatten());var c=[];return $A(d.childNodes).each(function(d){if(d.tagName&&d.tagName.toUpperCase()==b&&(!a||Element.classNames(d).detect(function(b){return a.include(b)}))&&c.push(d),e){var f=Element.findChildren(d,a,e,b);f&&c.push(f)}}),c.length>0?c.flatten():[]},Element.offsetSize=function(b,a){return b['offset'+(a=='vertical'||a=='height'?'Height':'Width')]},typeof Effect=='undefined')throw"controls.js requires including script.aculo.us' effects.js library";if(Autocompleter={},Autocompleter.Base=Class.create({baseInitialize:function(a,c,b){a=$(a),this.element=a,this.update=$(c),this.hasFocus=!1,this.changed=!1,this.active=!1,this.index=0,this.entryCount=0,this.oldElementValue=this.element.value,this.setOptions?this.setOptions(b):this.options=b||{},this.options.paramName=this.options.paramName||this.element.name,this.options.tokens=this.options.tokens||[],this.options.frequency=this.options.frequency||.4,this.options.minChars=this.options.minChars||1,this.options.onShow=this.options.onShow||function(b,a){(!a.style.position||a.style.position=='absolute')&&(a.style.position='absolute',Position.clone(b,a,{setHeight:!1,offsetTop:b.offsetHeight})),Effect.Appear(a,{duration:.15})},this.options.onHide=this.options.onHide||function(b,a){new Effect.Fade(a,{duration:.15})},typeof this.options.tokens=='string'&&(this.options.tokens=new Array(this.options.tokens)),this.options.tokens.include('\n')||this.options.tokens.push('\n'),this.observer=null,this.element.setAttribute('autocomplete','off'),Element.hide(this.update),Event.observe(this.element,'blur',this.onBlur.bindAsEventListener(this)),Event.observe(this.element,'keydown',this.onKeyPress.bindAsEventListener(this))},show:function(){Element.getStyle(this.update,'display')=='none'&&this.options.onShow(this.element,this.update),!this.iefix&&Prototype.Browser.IE&&Element.getStyle(this.update,'position')=='absolute'&&(new Insertion.After(this.update,'<iframe id="'+this.update.id+'_iefix" '+'style="display:none;position:absolute;filter:progid:DXImageTransform.Microsoft.Alpha(opacity=0);" '+'src="javascript:false;" frameborder="0" scrolling="no"></iframe>'),this.iefix=$(this.update.id+'_iefix')),this.iefix&&setTimeout(this.fixIEOverlapping.bind(this),50)},fixIEOverlapping:function(){Position.clone(this.update,this.iefix,{setTop:!this.update.style.height}),this.iefix.style.zIndex=1,this.update.style.zIndex=2,Element.show(this.iefix)},hide:function(){this.stopIndicator(),Element.getStyle(this.update,'display')!='none'&&this.options.onHide(this.element,this.update),this.iefix&&Element.hide(this.iefix)},startIndicator:function(){this.options.indicator&&Element.show(this.options.indicator)},stopIndicator:function(){this.options.indicator&&Element.hide(this.options.indicator)},onKeyPress:function(a){if(this.active)switch(a.keyCode){case Event.KEY_TAB:case Event.KEY_RETURN:this.selectEntry(),Event.stop(a);case Event.KEY_ESC:this.hide(),this.active=!1,Event.stop(a);return;case Event.KEY_LEFT:case Event.KEY_RIGHT:return;case Event.KEY_UP:this.markPrevious(),this.render(),Event.stop(a);return;case Event.KEY_DOWN:this.markNext(),this.render(),Event.stop(a);return}else if(a.keyCode==Event.KEY_TAB||a.keyCode==Event.KEY_RETURN||Prototype.Browser.WebKit>0&&a.keyCode==0)return;this.changed=!0,this.hasFocus=!0,this.observer&&clearTimeout(this.observer),this.observer=setTimeout(this.onObserverEvent.bind(this),this.options.frequency*1e3)},activate:function(){this.changed=!1,this.hasFocus=!0,this.getUpdatedChoices()},onHover:function(a){var b=Event.findElement(a,'LI');this.index!=b.autocompleteIndex&&(this.index=b.autocompleteIndex,this.render()),Event.stop(a)},onClick:function(a){var b=Event.findElement(a,'LI');this.index=b.autocompleteIndex,this.selectEntry(),this.hide()},onBlur:function(a){setTimeout(this.hide.bind(this),250),this.hasFocus=!1,this.active=!1},render:function(){if(this.entryCount>0){for(var a=0;a<this.entryCount;a++)this.index==a?Element.addClassName(this.getEntry(a),"selected"):Element.removeClassName(this.getEntry(a),"selected");this.hasFocus&&(this.show(),this.active=!0)}else this.active=!1,this.hide()},markPrevious:function(){this.index>0?this.index--:this.index=this.entryCount-1,this.getEntry(this.index).scrollIntoView(!0)},markNext:function(){this.index<this.entryCount-1?this.index++:this.index=0,this.getEntry(this.index).scrollIntoView(!1)},getEntry:function(a){return this.update.firstChild.childNodes[a]},getCurrentEntry:function(){return this.getEntry(this.index)},selectEntry:function(){this.active=!1,this.updateElement(this.getCurrentEntry())},updateElement:function(c){var a,d,b,e,f;if(this.options.updateElement){this.options.updateElement(c);return}a='',this.options.select?(d=$(c).select('.'+this.options.select)||[],d.length>0&&(a=Element.collectTextNodes(d[0],this.options.select))):a=Element.collectTextNodesIgnoreClass(c,'informal'),b=this.getTokenBounds(),b[0]!=-1?(e=this.element.value.substr(0,b[0]),f=this.element.value.substr(b[0]).match(/^\s+/),f&&(e+=f[0]),this.element.value=e+a+this.element.value.substr(b[1])):this.element.value=a,this.oldElementValue=this.element.value,this.element.focus(),this.options.afterUpdateElement&&this.options.afterUpdateElement(this.element,c)},updateChoices:function(c){var a,b;if(!this.changed&&this.hasFocus){if(this.update.innerHTML=c,Element.cleanWhitespace(this.update),Element.cleanWhitespace(this.update.down()),this.update.firstChild&&this.update.down().childNodes){this.entryCount=this.update.down().childNodes.length;for(a=0;a<this.entryCount;a++)b=this.getEntry(a),b.autocompleteIndex=a,this.addObservers(b)}else this.entryCount=0;this.stopIndicator(),this.index=0,this.entryCount==1&&this.options.autoSelect?(this.selectEntry(),this.hide()):this.render()}},addObservers:function(a){Event.observe(a,"mouseover",this.onHover.bindAsEventListener(this)),Event.observe(a,"click",this.onClick.bindAsEventListener(this))},onObserverEvent:function(){this.changed=!1,this.tokenBounds=null,this.getToken().length>=this.options.minChars?this.getUpdatedChoices():(this.active=!1,this.hide()),this.oldElementValue=this.element.value},getToken:function(){var a=this.getTokenBounds();return this.element.value.substring(a[0],a[1]).strip()},getTokenBounds:function(){var b,d,g,e,f,a,c,h;if(null!=this.tokenBounds)return this.tokenBounds;if(b=this.element.value,b.strip().empty())return[-1,0];d=arguments.callee.getFirstDifferencePos(b,this.oldElementValue),g=d==this.oldElementValue.length?1:0,e=-1,f=b.length;for(c=0,h=this.options.tokens.length;c<h;++c)a=b.lastIndexOf(this.options.tokens[c],d+g-1),a>e&&(e=a),a=b.indexOf(this.options.tokens[c],d+g),-1!=a&&a<f&&(f=a);return this.tokenBounds=[e+1,f]}}),Autocompleter.Base.prototype.getTokenBounds.getFirstDifferencePos=function(b,c){for(var d=Math.min(b.length,c.length),a=0;a<d;++a)if(b[a]!=c[a])return a;return d},Ajax.Autocompleter=Class.create(Autocompleter.Base,{initialize:function(a,b,c,d){this.baseInitialize(a,b,d),this.options.asynchronous=!0,this.options.onComplete=this.onComplete.bind(this),this.options.defaultParams=this.options.parameters||null,this.url=c},getUpdatedChoices:function(){this.startIndicator();var a=encodeURIComponent(this.options.paramName)+'='+encodeURIComponent(this.getToken());this.options.parameters=this.options.callback?this.options.callback(this.element,a):a,this.options.defaultParams&&(this.options.parameters+='&'+this.options.defaultParams),new Ajax.Request(this.url,this.options)},onComplete:function(a){this.updateChoices(a.responseText)}}),Autocompleter.Local=Class.create(Autocompleter.Base,{initialize:function(a,b,c,d){this.baseInitialize(a,b,d),this.options.array=c},getUpdatedChoices:function(){this.updateChoices(this.options.selector(this))},setOptions:function(a){this.options=Object.extend({choices:10,partialSearch:!0,partialChars:2,ignoreCase:!0,fullSearch:!1,selector:function(d){for(var e=[],f=[],c=d.getToken(),h=0,g=0,a,b;g<d.options.array.length&&e.length<d.options.choices;g++)for(a=d.options.array[g],b=d.options.ignoreCase?a.toLowerCase().indexOf(c.toLowerCase()):a.indexOf(c);b!=-1;){if(b==0&&a.length!=c.length){e.push("<li><strong>"+a.substr(0,c.length)+"</strong>"+a.substr(c.length)+"</li>");break}if(c.length>=d.options.partialChars&&d.options.partialSearch&&b!=-1){if(d.options.fullSearch||/\s/.test(a.substr(b-1,1))){f.push("<li>"+a.substr(0,b)+"<strong>"+a.substr(b,c.length)+"</strong>"+a.substr(b+c.length)+"</li>");break}}b=d.options.ignoreCase?a.toLowerCase().indexOf(c.toLowerCase(),b+1):a.indexOf(c,b+1)}return f.length&&(e=e.concat(f.slice(0,d.options.choices-e.length))),"<ul>"+e.join('')+"</ul>"}},a||{})}}),Field.scrollFreeActivate=function(a){setTimeout(function(){Field.activate(a)},1)},Ajax.InPlaceEditor=Class.create({initialize:function(a,c,b){this.url=c,this.element=a=$(a),this.prepareOptions(),this._controls={},arguments.callee.dealWithDeprecatedOptions(b),Object.extend(this.options,b||{}),!this.options.formId&&this.element.id&&(this.options.formId=this.element.id+'-inplaceeditor',$(this.options.formId)&&(this.options.formId='')),this.options.externalControl&&(this.options.externalControl=$(this.options.externalControl)),this.options.externalControl||(this.options.externalControlOnly=!1),this._originalBackground=this.element.getStyle('background-color')||'transparent',this.element.title=this.options.clickToEditText,this._boundCancelHandler=this.handleFormCancellation.bind(this),this._boundComplete=(this.options.onComplete||Prototype.emptyFunction).bind(this),this._boundFailureHandler=this.handleAJAXFailure.bind(this),this._boundSubmitHandler=this.handleFormSubmission.bind(this),this._boundWrapperHandler=this.wrapUp.bind(this),this.registerListeners()},checkForEscapeOrReturn:function(a){if(!this._editing||a.ctrlKey||a.altKey||a.shiftKey)return;Event.KEY_ESC==a.keyCode?this.handleFormCancellation(a):Event.KEY_RETURN==a.keyCode&&this.handleFormSubmission(a)},createControl:function(a,g,d){var e=this.options[a+'Control'],f=this.options[a+'Text'],c,b;'button'==e?(c=document.createElement('input'),c.type='submit',c.value=f,c.className='editor_'+a+'_button','cancel'==a&&(c.onclick=this._boundCancelHandler),this._form.appendChild(c),this._controls[a]=c):'link'==e&&(b=document.createElement('a'),b.href='#',b.appendChild(document.createTextNode(f)),b.onclick='cancel'==a?this._boundCancelHandler:this._boundSubmitHandler,b.className='editor_'+a+'_link',d&&(b.className+=' '+d),this._form.appendChild(b),this._controls[a]=b)},createEditField:function(){var c=this.options.loadTextURL?this.options.loadingText:this.getText(),a,b;1>=this.options.rows&&!/\r|\n/.test(this.getText())?(a=document.createElement('input'),a.type='text',b=this.options.size||this.options.cols||0,0<b&&(a.size=b)):(a=document.createElement('textarea'),a.rows=1>=this.options.rows?this.options.autoRows:this.options.rows,a.cols=this.options.cols||40),a.name=this.options.paramName,a.value=c,a.className='editor_field',this.options.submitOnBlur&&(a.onblur=this._boundSubmitHandler),this._controls.editor=a,this.options.loadTextURL&&this.loadExternalText(),this._form.appendChild(this._controls.editor)},createForm:function(){var b=this;function a(c,d){var a=b.options['text'+c+'Controls'];if(!a||d===!1)return;b._form.appendChild(document.createTextNode(a))}this._form=$(document.createElement('form')),this._form.id=this.options.formId,this._form.addClassName(this.options.formClassName),this._form.onsubmit=this._boundSubmitHandler,this.createEditField(),'textarea'==this._controls.editor.tagName.toLowerCase()&&this._form.appendChild(document.createElement('br')),this.options.onFormCustomization&&this.options.onFormCustomization(this,this._form),a('Before',this.options.okControl||this.options.cancelControl),this.createControl('ok',this._boundSubmitHandler),a('Between',this.options.okControl&&this.options.cancelControl),this.createControl('cancel',this._boundCancelHandler,'editor_cancel'),a('After',this.options.okControl||this.options.cancelControl)},destroy:function(){this._oldInnerHTML&&(this.element.innerHTML=this._oldInnerHTML),this.leaveEditMode(),this.unregisterListeners()},enterEditMode:function(a){if(this._saving||this._editing)return;this._editing=!0,this.triggerCallback('onEnterEditMode'),this.options.externalControl&&this.options.externalControl.hide(),this.element.hide(),this.createForm(),this.element.parentNode.insertBefore(this._form,this.element),this.options.loadTextURL||this.postProcessEditField(),a&&Event.stop(a)},enterHover:function(a){if(this.options.hoverClassName&&this.element.addClassName(this.options.hoverClassName),this._saving)return;this.triggerCallback('onEnterHover')},getText:function(){return this.element.innerHTML.unescapeHTML()},handleAJAXFailure:function(a){this.triggerCallback('onFailure',a),this._oldInnerHTML&&(this.element.innerHTML=this._oldInnerHTML,this._oldInnerHTML=null)},handleFormCancellation:function(a){this.wrapUp(),a&&Event.stop(a)},handleFormSubmission:function(c){var d=this._form,e=$F(this._controls.editor),a,b;this.prepareSubmission(),a=this.options.callback(d,e)||'',Object.isString(a)&&(a=a.toQueryParams()),a.editorId=this.element.id,this.options.htmlResponse?(b=Object.extend({evalScripts:!0},this.options.ajaxOptions),Object.extend(b,{parameters:a,onComplete:this._boundWrapperHandler,onFailure:this._boundFailureHandler}),new Ajax.Updater({success:this.element},this.url,b)):(b=Object.extend({method:'get'},this.options.ajaxOptions),Object.extend(b,{parameters:a,onComplete:this._boundWrapperHandler,onFailure:this._boundFailureHandler}),new Ajax.Request(this.url,b)),c&&Event.stop(c)},leaveEditMode:function(){this.element.removeClassName(this.options.savingClassName),this.removeForm(),this.leaveHover(),this.element.style.backgroundColor=this._originalBackground,this.element.show(),this.options.externalControl&&this.options.externalControl.show(),this._saving=!1,this._editing=!1,this._oldInnerHTML=null,this.triggerCallback('onLeaveEditMode')},leaveHover:function(a){if(this.options.hoverClassName&&this.element.removeClassName(this.options.hoverClassName),this._saving)return;this.triggerCallback('onLeaveHover')},loadExternalText:function(){this._form.addClassName(this.options.loadingClassName),this._controls.editor.disabled=!0;var a=Object.extend({method:'get'},this.options.ajaxOptions);Object.extend(a,{parameters:'editorId='+encodeURIComponent(this.element.id),onComplete:Prototype.emptyFunction,onSuccess:function(b){this._form.removeClassName(this.options.loadingClassName);var a=b.responseText;this.options.stripLoadedTextTags&&(a=a.stripTags()),this._controls.editor.value=a,this._controls.editor.disabled=!1,this.postProcessEditField()}.bind(this),onFailure:this._boundFailureHandler}),new Ajax.Request(this.options.loadTextURL,a)},postProcessEditField:function(){var a=this.options.fieldPostCreation;a&&$(this._controls.editor)['focus'==a?'focus':'activate']()},prepareOptions:function(){this.options=Object.clone(Ajax.InPlaceEditor.DefaultOptions),Object.extend(this.options,Ajax.InPlaceEditor.DefaultCallbacks),[this._extraDefaultOptions].flatten().compact().each(function(a){Object.extend(this.options,a)}.bind(this))},prepareSubmission:function(){this._saving=!0,this.removeForm(),this.leaveHover(),this.showSaving()},registerListeners:function(){this._listeners={};var a;$H(Ajax.InPlaceEditor.Listeners).each(function(b){a=this[b.value].bind(this),this._listeners[b.key]=a,this.options.externalControlOnly||this.element.observe(b.key,a),this.options.externalControl&&this.options.externalControl.observe(b.key,a)}.bind(this))},removeForm:function(){if(!this._form)return;this._form.remove(),this._form=null,this._controls={}},showSaving:function(){this._oldInnerHTML=this.element.innerHTML,this.element.innerHTML=this.options.savingText,this.element.addClassName(this.options.savingClassName),this.element.style.backgroundColor=this._originalBackground,this.element.show()},triggerCallback:function(a,b){'function'==typeof this.options[a]&&this.options[a](this,b)},unregisterListeners:function(){$H(this._listeners).each(function(a){this.options.externalControlOnly||this.element.stopObserving(a.key,a.value),this.options.externalControl&&this.options.externalControl.stopObserving(a.key,a.value)}.bind(this))},wrapUp:function(a){this.leaveEditMode(),this._boundComplete(a,this.element)}}),Object.extend(Ajax.InPlaceEditor.prototype,{dispose:Ajax.InPlaceEditor.prototype.destroy}),Ajax.InPlaceCollectionEditor=Class.create(Ajax.InPlaceEditor,{initialize:function(a,b,c,d){this._extraDefaultOptions=Ajax.InPlaceCollectionEditor.DefaultOptions,a(b,c,d)},createEditField:function(){var a=document.createElement('select');a.name=this.options.paramName,a.size=1,this._controls.editor=a,this._collection=this.options.collection||[],this.options.loadCollectionURL?this.loadCollection():this.checkForExternalText(),this._form.appendChild(this._controls.editor)},loadCollection:function(){this._form.addClassName(this.options.loadingClassName),this.showLoadingText(this.options.loadingCollectionText);var a=Object.extend({method:'get'},this.options.ajaxOptions);Object.extend(a,{parameters:'editorId='+encodeURIComponent(this.element.id),onComplete:Prototype.emptyFunction,onSuccess:function(b){var a=b.responseText.strip();if(!/^\[.*\]$/.test(a))throw'Server returned an invalid collection representation.';this._collection=eval(a),this.checkForExternalText()}.bind(this),onFailure:this.onFailure}),new Ajax.Request(this.options.loadCollectionURL,a)},showLoadingText:function(b){this._controls.editor.disabled=!0;var a=this._controls.editor.firstChild;a||(a=document.createElement('option'),a.value='',this._controls.editor.appendChild(a),a.selected=!0),a.update((b||'').stripScripts().stripTags())},checkForExternalText:function(){this._text=this.getText(),this.options.loadTextURL?this.loadExternalText():this.buildOptionList()},loadExternalText:function(){this.showLoadingText(this.options.loadingText);var a=Object.extend({method:'get'},this.options.ajaxOptions);Object.extend(a,{parameters:'editorId='+encodeURIComponent(this.element.id),onComplete:Prototype.emptyFunction,onSuccess:function(a){this._text=a.responseText.strip(),this.buildOptionList()}.bind(this),onFailure:this.onFailure}),new Ajax.Request(this.options.loadTextURL,a)},buildOptionList:function(){var b,c,a;this._form.removeClassName(this.options.loadingClassName),this._collection=this._collection.map(function(a){return 2===a.length?a:[a,a].flatten()}),b='value'in this.options?this.options.value:this._text,c=this._collection.any(function(a){return a[0]==b}.bind(this)),this._controls.editor.update(''),this._collection.each(function(d,e){a=document.createElement('option'),a.value=d[0],a.selected=c?d[0]==b:0==e,a.appendChild(document.createTextNode(d[1])),this._controls.editor.appendChild(a)}.bind(this)),this._controls.editor.disabled=!1,Field.scrollFreeActivate(this._controls.editor)}}),Ajax.InPlaceEditor.prototype.initialize.dealWithDeprecatedOptions=function(a){if(!a)return;function b(b,c){if(b in a||c===void 0)return;a[b]=c}b('cancelControl',a.cancelLink?'link':a.cancelButton?'button':a.cancelLink==a.cancelButton!=!1&&void 0),b('okControl',a.okLink?'link':a.okButton?'button':a.okLink==a.okButton!=!1&&void 0),b('highlightColor',a.highlightcolor),b('highlightEndColor',a.highlightendcolor)},Object.extend(Ajax.InPlaceEditor,{DefaultOptions:{ajaxOptions:{},autoRows:3,cancelControl:'link',cancelText:'cancel',clickToEditText:'Click to edit',externalControl:null,externalControlOnly:!1,fieldPostCreation:'activate',formClassName:'inplaceeditor-form',formId:null,highlightColor:'#ffff99',highlightEndColor:'#ffffff',hoverClassName:'',htmlResponse:!0,loadingClassName:'inplaceeditor-loading',loadingText:'Loading...',okControl:'button',okText:'ok',paramName:'value',rows:1,savingClassName:'inplaceeditor-saving',savingText:'Saving...',size:0,stripLoadedTextTags:!1,submitOnBlur:!1,textAfterControls:'',textBeforeControls:'',textBetweenControls:''},DefaultCallbacks:{callback:function(a){return Form.serialize(a)},onComplete:function(b,a){new Effect.Highlight(a,{startcolor:this.options.highlightColor,keepBackgroundImage:!0})},onEnterEditMode:null,onEnterHover:function(a){a.element.style.backgroundColor=a.options.highlightColor,a._effect&&a._effect.cancel()},onFailure:function(a,b){alert('Error communication with the server: '+a.responseText.stripTags())},onFormCustomization:null,onLeaveEditMode:null,onLeaveHover:function(a){a._effect=new Effect.Highlight(a.element,{startcolor:a.options.highlightColor,endcolor:a.options.highlightEndColor,restorecolor:a._originalBackground,keepBackgroundImage:!0})}},Listeners:{click:'enterEditMode',keydown:'checkForEscapeOrReturn',mouseover:'enterHover',mouseout:'leaveHover'}}),Ajax.InPlaceCollectionEditor.DefaultOptions={loadingCollectionText:'Loading options...'},Form.Element.DelayedObserver=Class.create({initialize:function(a,b,c){this.delay=b||.5,this.element=$(a),this.callback=c,this.timer=null,this.lastValue=$F(this.element),Event.observe(this.element,'keyup',this.delayedListener.bindAsEventListener(this))},delayedListener:function(a){if(this.lastValue==$F(this.element))return;this.timer&&clearTimeout(this.timer),this.timer=setTimeout(this.onTimerEvent.bind(this),this.delay*1e3),this.lastValue=$F(this.element)},onTimerEvent:function(){this.timer=null,this.callback(this.element,$F(this.element))}}),Control||(Control={}),Control.Slider=Class.create({initialize:function(b,c,d){var a=this;Object.isArray(b)?this.handles=b.collect(function(a){return $(a)}):this.handles=[$(b)],this.track=$(c),this.options=d||{},this.axis=this.options.axis||'horizontal',this.increment=this.options.increment||1,this.step=parseInt(this.options.step||'1'),this.range=this.options.range||$R(0,1),this.value=0,this.values=this.handles.map(function(){return 0}),this.spans=!!this.options.spans&&this.options.spans.map(function(a){return $(a)}),this.options.startSpan=$(this.options.startSpan||null),this.options.endSpan=$(this.options.endSpan||null),this.restricted=this.options.restricted||!1,this.maximum=this.options.maximum||this.range.end,this.minimum=this.options.minimum||this.range.start,this.alignX=parseInt(this.options.alignX||'0'),this.alignY=parseInt(this.options.alignY||'0'),this.trackLength=this.maximumOffset()-this.minimumOffset(),this.handleLength=this.isVertical()?this.handles[0].offsetHeight!=0?this.handles[0].offsetHeight:this.handles[0].style.height.replace(/px$/,""):this.handles[0].offsetWidth!=0?this.handles[0].offsetWidth:this.handles[0].style.width.replace(/px$/,""),this.active=!1,this.dragging=!1,this.disabled=!1,this.options.disabled&&this.setDisabled(),this.allowedValues=!!this.options.values&&this.options.values.sortBy(Prototype.K),this.allowedValues&&(this.minimum=this.allowedValues.min(),this.maximum=this.allowedValues.max()),this.eventMouseDown=this.startDrag.bindAsEventListener(this),this.eventMouseUp=this.endDrag.bindAsEventListener(this),this.eventMouseMove=this.update.bindAsEventListener(this),this.handles.each(function(c,b){b=a.handles.length-1-b,a.setValue(parseFloat((Object.isArray(a.options.sliderValue)?a.options.sliderValue[b]:a.options.sliderValue)||a.range.start),b),c.makePositioned().observe("mousedown",a.eventMouseDown)}),this.track.observe("mousedown",this.eventMouseDown),document.observe("mouseup",this.eventMouseUp),document.observe("mousemove",this.eventMouseMove),this.initialized=!0},dispose:function(){var a=this;Event.stopObserving(this.track,"mousedown",this.eventMouseDown),Event.stopObserving(document,"mouseup",this.eventMouseUp),Event.stopObserving(document,"mousemove",this.eventMouseMove),this.handles.each(function(b){Event.stopObserving(b,"mousedown",a.eventMouseDown)})},setDisabled:function(){this.disabled=!0},setEnabled:function(){this.disabled=!1},getNearestValue:function(a){var b,c;return this.allowedValues?a>=this.allowedValues.max()?this.allowedValues.max():a<=this.allowedValues.min()?this.allowedValues.min():(b=Math.abs(this.allowedValues[0]-a),c=this.allowedValues[0],this.allowedValues.each(function(d){var e=Math.abs(d-a);e<=b&&(c=d,b=e)}),c):a>this.range.end?this.range.end:a<this.range.start?this.range.start:a},setValue:function(b,a){this.active||(this.activeHandleIdx=a||0,this.activeHandle=this.handles[this.activeHandleIdx],this.updateStyles()),a=a||this.activeHandleIdx||0,this.initialized&&this.restricted&&(a>0&&b<this.values[a-1]&&(b=this.values[a-1]),a<this.handles.length-1&&b>this.values[a+1]&&(b=this.values[a+1])),b=this.getNearestValue(b),this.values[a]=b,this.value=this.values[0],this.handles[a].style[this.isVertical()?'top':'left']=this.translateToPx(b),this.drawSpans(),(!this.dragging||!this.event)&&this.updateFinished()},setValueBy:function(b,a){this.setValue(this.values[a||this.activeHandleIdx||0]+b,a||this.activeHandleIdx||0)},translateToPx:function(a){return Math.round((this.trackLength-this.handleLength)/(this.range.end-this.range.start)*(a-this.range.start))+"px"},translateToValue:function(a){return a/(this.trackLength-this.handleLength)*(this.range.end-this.range.start)+this.range.start},getRange:function(a){var b=this.values.sortBy(Prototype.K);return a=a||0,$R(b[a],b[a+1])},minimumOffset:function(){return this.isVertical()?this.alignY:this.alignX},maximumOffset:function(){return this.isVertical()?(this.track.offsetHeight!=0?this.track.offsetHeight:this.track.style.height.replace(/px$/,""))-this.alignY:(this.track.offsetWidth!=0?this.track.offsetWidth:this.track.style.width.replace(/px$/,""))-this.alignX},isVertical:function(){return this.axis=='vertical'},drawSpans:function(){var a=this;this.spans&&$R(0,this.spans.length-1).each(function(b){a.setSpan(a.spans[b],a.getRange(b))}),this.options.startSpan&&this.setSpan(this.options.startSpan,$R(0,this.values.length>1?this.getRange(0).min():this.value)),this.options.endSpan&&this.setSpan(this.options.endSpan,$R(this.values.length>1?this.getRange(this.spans.length-1).max():this.value,this.maximum))},setSpan:function(b,a){this.isVertical()?(b.style.top=this.translateToPx(a.start),b.style.height=this.translateToPx(a.end-a.start+this.range.start)):(b.style.left=this.translateToPx(a.start),b.style.width=this.translateToPx(a.end-a.start+this.range.start))},updateStyles:function(){this.handles.each(function(a){Element.removeClassName(a,'selected')}),Element.addClassName(this.activeHandle,'selected')},startDrag:function(d){var b,c,e,a;if(Event.isLeftClick(d)){if(!this.disabled)if(this.active=!0,b=Event.element(d),c=[Event.pointerX(d),Event.pointerY(d)],e=b,e==this.track)a=this.track.cumulativeOffset(),this.event=d,this.setValue(this.translateToValue((this.isVertical()?c[1]-a[1]:c[0]-a[0])-this.handleLength/2)),a=this.activeHandle.cumulativeOffset(),this.offsetX=c[0]-a[0],this.offsetY=c[1]-a[1];else{while(this.handles.indexOf(b)==-1&&b.parentNode)b=b.parentNode;this.handles.indexOf(b)!=-1&&(this.activeHandle=b,this.activeHandleIdx=this.handles.indexOf(this.activeHandle),this.updateStyles(),a=this.activeHandle.cumulativeOffset(),this.offsetX=c[0]-a[0],this.offsetY=c[1]-a[1])}Event.stop(d)}},update:function(a){this.active&&(this.dragging||(this.dragging=!0),this.draw(a),Prototype.Browser.WebKit&&window.scrollBy(0,0),Event.stop(a))},draw:function(b){var a=[Event.pointerX(b),Event.pointerY(b)],c=this.track.cumulativeOffset();a[0]-=this.offsetX+c[0],a[1]-=this.offsetY+c[1],this.event=b,this.setValue(this.translateToValue(this.isVertical()?a[1]:a[0])),this.initialized&&this.options.onSlide&&this.options.onSlide(this.values.length>1?this.values:this.value,this)},endDrag:function(a){this.active&&this.dragging&&(this.finishDrag(a,!0),Event.stop(a)),this.active=!1,this.dragging=!1},finishDrag:function(a,b){this.active=!1,this.dragging=!1,this.updateFinished()},updateFinished:function(){this.initialized&&this.options.onChange&&this.options.onChange(this.values.length>1?this.values:this.value,this),this.event=null}}),Prototip={Version:'2.2.2'},Object.extend(Prototip,{REQUIRED_Prototype:"1.7",support:{canvas:!!document.createElement("canvas").getContext},insertScript:function(a){try{document.write("<script type='text/javascript' src='"+a+"'></script>")}catch(b){$$("head")[0].insert(new Element("script",{src:a,type:"text/javascript"}))}},start:function(){this.require("Prototype");var a=/prototip([\w\d-_.]+)?\.js(.*)/;this.path=(($$("script[src]").find(function(b){return b.src.match(a)})||{}).src||"").replace(a,""),Tips.paths=function(a){return{images:/^(https?:\/\/|\/)/.test(a.images)?a.images:this.path+a.images,javascript:/^(https?:\/\/|\/)/.test(a.javascript)?a.javascript:this.path+a.javascript}}.bind(this)(Tips.options.paths),Prototip.Styles||this.insertScript(Tips.paths.javascript+"styles.js"),this.support.canvas||(document.documentMode>=8&&!document.namespaces.ns_vml?document.namespaces.add("ns_vml","urn:schemas-microsoft-com:vml","#default#VML"):document.observe("dom:loaded",function(){var a=document.createStyleSheet();a.cssText="ns_vml\\:*{behavior:url(#default#VML)}"})),Tips.initialize(),Element.observe(window,"unload",this.unload)},require:function(a){if(typeof window[a]=="undefined"||this.convertVersionString(window[a].Version)<this.convertVersionString(this["REQUIRED_"+a]))throw"Prototip requires "+a+" >= "+this["REQUIRED_"+a]},convertVersionString:function(b){var a=b.replace(/_.*|\./g,"");return a=parseInt(a+"0".times(4-a.length)),b.indexOf("_")>-1?a-1:a},toggleInt:function(a){return a>0?-1*a:a.abs()},unload:function(){Tips.removeAll()}}),Object.extend(Tips,function(){function a(a){if(!a)return;a.deactivate(),a.tooltip&&(a.wrapper.remove(),Tips.fixIE&&a.iframeShim.remove()),Tips.tips=Tips.tips.without(a)}return{tips:[],visible:[],initialize:function(){this.zIndexTop=this.zIndex},_inverse:{left:"right",right:"left",top:"bottom",bottom:"top",middle:"middle",horizontal:"vertical",vertical:"horizontal"},_stemTranslation:{width:"horizontal",height:"vertical"},inverseStem:function(a){return!!arguments[1]?this._inverse[a]:a},fixIE:function(b){var a=new RegExp("MSIE ([\\d.]+)").exec(b);return!!a&&parseFloat(a[1])<7}(navigator.userAgent),WebKit419:Prototype.Browser.WebKit&&!document.evaluate,add:function(a){this.tips.push(a)},remove:function(g){for(var b=0,f=this.tips.length,d,e=[],c;b<f;b++)c=this.tips[b],!d&&c.element==$(g)?d=c:c.element.parentNode||e.push(c);a(d);for(b=0,f=e.length;b<f;b++)c=e[b],a(c);g.prototip=null},removeAll:function(){for(var b=0,c=this.tips.length;b<c;b++)a(this.tips[b])},raise:function(a){if(a==this._highest)return;if(this.visible.length===0){this.zIndexTop=this.options.zIndex;for(var b=0,c=this.tips.length;b<c;b++)this.tips[b].wrapper.setStyle({zIndex:this.options.zIndex})}a.wrapper.setStyle({zIndex:this.zIndexTop++}),a.loader&&a.loader.setStyle({zIndex:this.zIndexTop}),this._highest=a},addVisibile:function(a){this.removeVisible(a),this.visible.push(a)},removeVisible:function(a){this.visible=this.visible.without(a)},hideAll:function(){Tips.visible.invoke("hide")},isChrome:function(a){return a||(a=32),navigator.userAgent.toLowerCase().indexOf('chrome/'+a)>-1},hook:function(h,g){var e,d,k,j,f,i,c,b,a;h=$(h),g=$(g),e=Object.extend({offset:{x:0,y:0},position:!1},arguments[2]||{}),d=e.mouse||g.cumulativeOffset(),d.left+=e.offset.x,d.top+=e.offset.y,k=e.mouse?[0,0]:g.cumulativeScrollOffset(),j=document.viewport.getScrollOffsets(),f=e.mouse?"mouseHook":"target",d.left+=-1*(k[0]-j[0]),this.isChrome('32')||(d.top+=-1*(k[1]-j[1])),e.mouse&&(i=[0,0],i.width=0,i.height=0),c={element:h.getDimensions()},b={element:Object.clone(d)},c[f]=e.mouse?i:g.getDimensions(),b[f]=Object.clone(d);for(a in b)switch(e[a]){case"topRight":case"rightTop":b[a].left+=c[a].width;break;case"topMiddle":b[a].left+=c[a].width/2;break;case"rightMiddle":b[a].left+=c[a].width,b[a].top+=c[a].height/2;break;case"bottomLeft":case"leftBottom":b[a].top+=c[a].height;break;case"bottomRight":case"rightBottom":b[a].left+=c[a].width,b[a].top+=c[a].height;break;case"bottomMiddle":b[a].left+=c[a].width/2,b[a].top+=c[a].height;break;case"leftMiddle":b[a].top+=c[a].height/2;break}return d.left+=-1*(b.element.left-b[f].left),d.top+=-1*(b.element.top-b[f].top),e.position&&h.setStyle({left:d.left+"px",top:d.top+"px"}),d}}}()),Tips.initialize(),Tip=Class.create({initialize:function(e,b){var c,a,d;if(this.element=$(e),!this.element){throw"Prototip: Element not available, cannot create a tooltip.";return}Tips.remove(this.element),c=Object.isString(b)||Object.isElement(b),a=c?arguments[2]||[]:b,this.content=c?b:null,a.style&&(a=Object.extend(Object.clone(Prototip.Styles[a.style]),a)),this.options=Object.extend(Object.extend({ajax:!1,border:0,borderColor:"#000000",radius:0,className:Tips.options.className,closeButton:Tips.options.closeButtons,delay:!(a.showOn&&a.showOn=="click")&&.14,hideAfter:!1,hideOn:"mouseleave",hideOthers:!1,hook:a.hook,offset:a.hook?{x:0,y:0}:{x:16,y:16},fixed:!!(a.hook&&!a.hook.mouse),showOn:"mousemove",stem:!1,style:"default",target:this.element,title:!1,viewport:!(a.hook&&!a.hook.mouse),width:!1},Prototip.Styles.default),a),this.target=$(this.options.target),this.radius=this.options.radius,this.border=this.radius>this.options.border?this.radius:this.options.border,this.options.images?this.images=this.options.images.include("://")?this.options.images:Tips.paths.images+this.options.images:this.images=Tips.paths.images+"styles/"+(this.options.style||"")+"/",this.images.endsWith("/")||(this.images+="/"),Object.isString(this.options.stem)&&(this.options.stem={position:this.options.stem}),this.options.stem.position&&(this.options.stem=Object.extend(Object.clone(Prototip.Styles[this.options.style].stem)||{},this.options.stem),this.options.stem.position=[this.options.stem.position.match(/[a-z]+/)[0].toLowerCase(),this.options.stem.position.match(/[A-Z][a-z]+/)[0].toLowerCase()],this.options.stem.orientation=["left","right"].member(this.options.stem.position[0])?"horizontal":"vertical",this.stemInverse={horizontal:!1,vertical:!1}),this.options.ajax&&(this.options.ajax.options=Object.extend({onComplete:Prototype.emptyFunction},this.options.ajax.options||{})),this.options.hook.mouse&&(d=this.options.hook.tip.match(/[a-z]+/)[0].toLowerCase(),this.mouseHook=Tips._inverse[d]+Tips._inverse[this.options.hook.tip.match(/[A-Z][a-z]+/)[0].toLowerCase()].capitalize()),this.fixSafari2=Tips.WebKit419&&this.radius,this.setup(),Tips.add(this),this.activate(),Prototip.extend(this)},setup:function(){this.wrapper=new Element("div",{className:"prototip"}).setStyle({zIndex:Tips.options.zIndex}),this.fixSafari2&&(this.wrapper.hide=function(){return this.setStyle("left:-9500px;top:-9500px;visibility:hidden;"),this},this.wrapper.show=function(){return this.setStyle("visibility:visible"),this},this.wrapper.visible=function(){return this.getStyle("visibility")=="visible"&&parseFloat(this.getStyle("top").replace("px",""))>-9500}),this.wrapper.hide(),Tips.fixIE&&(this.iframeShim=new Element("iframe",{className:"iframeShim",src:"javascript:false;",frameBorder:0}).setStyle({display:"none",zIndex:Tips.options.zIndex-1,opacity:0})),this.options.ajax&&(this.showDelayed=this.showDelayed.wrap(this.ajaxShow)),this.tip=new Element("div",{className:"content"}),this.title=new Element("div",{className:"title"}).hide(),(this.options.closeButton||this.options.hideOn.element&&this.options.hideOn.element=="closeButton")&&(this.closeButton=new Element("div",{className:"close"}).setPngBackground(this.images+"close.png"))},build:function(){if(document.loaded)return this._build(),this._isBuilding=!0,!0;if(!this._isBuilding)return document.observe("dom:loaded",this._build),!1},_build:function(){var b,e,a,f,c,d,g;$(document.body).insert(this.wrapper),Tips.fixIE&&$(document.body).insert(this.iframeShim),this.options.ajax&&$(document.body).insert(this.loader=new Element("div",{className:"prototipLoader"}).setPngBackground(this.images+"loader.gif").hide()),b="wrapper",this.options.stem.position&&(this.stem=new Element("div",{className:"prototip_Stem"}).setStyle({height:this.options.stem[this.options.stem.orientation=="vertical"?"height":"width"]+"px"}),e=this.options.stem.orientation=="horizontal",this[b].insert(this.stemWrapper=new Element("div",{className:"prototip_StemWrapper clearfix"}).insert(this.stemBox=new Element("div",{className:"prototip_StemBox clearfix"}))),this.stem.insert(this.stemImage=new Element("div",{className:"prototip_StemImage"}).setStyle({height:this.options.stem[e?"width":"height"]+"px",width:this.options.stem[e?"height":"width"]+"px"})),Tips.fixIE&&!this.options.stem.position[1].toUpperCase().include("MIDDLE")&&this.stemImage.setStyle({display:"inline"}),b="stemBox"),this.border&&(a=this.border,this[b].insert(this.borderFrame=new Element("ul",{className:"borderFrame"}).insert(this.borderTop=new Element("li",{className:"borderTop borderRow"}).setStyle("height: "+a+"px").insert(new Element("div",{className:"prototip_CornerWrapper prototip_CornerWrapperTopLeft"}).insert(new Element("div",{className:"prototip_Corner"}))).insert(f=new Element("div",{className:"prototip_BetweenCorners"}).setStyle({height:a+"px"}).insert(new Element("div",{className:"prototip_Between"}).setStyle({margin:"0 "+a+"px",height:a+"px"}))).insert(new Element("div",{className:"prototip_CornerWrapper prototip_CornerWrapperTopRight"}).insert(new Element("div",{className:"prototip_Corner"})))).insert(this.borderMiddle=new Element("li",{className:"borderMiddle borderRow"}).insert(this.borderCenter=new Element("div",{className:"borderCenter"}).setStyle("padding: 0 "+a+"px"))).insert(this.borderBottom=new Element("li",{className:"borderBottom borderRow"}).setStyle("height: "+a+"px").insert(new Element("div",{className:"prototip_CornerWrapper prototip_CornerWrapperBottomLeft"}).insert(new Element("div",{className:"prototip_Corner"}))).insert(f.cloneNode(!0)).insert(new Element("div",{className:"prototip_CornerWrapper prototip_CornerWrapperBottomRight"}).insert(new Element("div",{className:"prototip_Corner"}))))),b="borderCenter",c=this.borderFrame.select(".prototip_Corner"),$w("tl tr bl br").each(function(d,b){this.radius>0?Prototip.createCorner(c[b],d,{backgroundColor:this.options.borderColor,border:a,radius:this.options.radius}):c[b].addClassName("prototip_Fill"),c[b].setStyle({width:a+"px",height:a+"px"}).addClassName("prototip_Corner"+d.capitalize())}.bind(this)),this.borderFrame.select(".prototip_Between",".borderMiddle",".prototip_Fill").invoke("setStyle",{backgroundColor:this.options.borderColor})),this[b].insert(this.tooltip=new Element("div",{className:"tooltip "+this.options.className}).insert(this.toolbar=new Element("div",{className:"toolbar"}).insert(this.title))),this.options.width&&(d=this.options.width,Object.isNumber(d)&&(d+="px"),this.tooltip.setStyle("width:"+d)),this.stem&&(g={},g[this.options.stem.orientation=="horizontal"?"top":"bottom"]=this.stem,this.wrapper.insert(g),this.positionStem()),this.tooltip.insert(this.tip),this.options.ajax||this._update({title:this.options.title,content:this.content})},_update:function(a){var e=this.wrapper.getStyle("visibility"),d,b,c;this.wrapper.setStyle("height:auto;width:auto;visibility:hidden").show(),this.border&&(this.borderTop.setStyle("height:0"),this.borderTop.setStyle("height:0")),a.title?(this.title.show().update(a.title),this.toolbar.show()):this.closeButton||(this.title.hide(),this.toolbar.hide()),Object.isElement(a.content)&&a.content.show(),(Object.isString(a.content)||Object.isElement(a.content))&&this.tip.update(a.content),this.tooltip.setStyle({width:this.tooltip.getWidth()+"px"}),this.wrapper.setStyle("visibility:visible").show(),this.tooltip.show(),d=this.tooltip.getDimensions(),b={width:d.width+"px"},c=[this.wrapper],Tips.fixIE&&c.push(this.iframeShim),this.closeButton&&(this.title.show().insert({top:this.closeButton}),this.toolbar.show()),(a.title||this.closeButton)&&this.toolbar.setStyle("width: 100%"),b.height=null,this.wrapper.setStyle({visibility:e}),this.tip.addClassName("clearfix"),(a.title||this.closeButton)&&this.title.addClassName("clearfix"),this.border&&(this.borderTop.setStyle("height:"+this.border+"px"),this.borderTop.setStyle("height:"+this.border+"px"),b="width: "+(d.width+2*this.border)+"px",c.push(this.borderFrame)),c.invoke("setStyle",b),this.stem&&(this.positionStem(),this.options.stem.orientation=="horizontal"&&this.wrapper.setStyle({width:this.wrapper.getWidth()+this.options.stem.height+"px"})),this.wrapper.hide()},activate:function(){var b,a,c;this.eventShow=this.showDelayed.bindAsEventListener(this),this.eventHide=this.hide.bindAsEventListener(this),this.options.fixed&&this.options.showOn=="mousemove"&&(this.options.showOn="mouseover"),this.options.showOn&&this.options.showOn==this.options.hideOn&&(this.eventToggle=this.toggle.bindAsEventListener(this),this.element.observe(this.options.showOn,this.eventToggle)),this.closeButton&&this.closeButton.observe("mouseover",function(a){a.setPngBackground(this.images+"close_hover.png")}.bind(this,this.closeButton)).observe("mouseout",function(a){a.setPngBackground(this.images+"close.png")}.bind(this,this.closeButton)),b={element:this.eventToggle?[]:[this.element],target:this.eventToggle?[]:[this.target],tip:this.eventToggle?[]:[this.wrapper],closeButton:[],none:[]},a=this.options.hideOn.element,this.hideElement=a||(this.options.hideOn?"element":"none"),this.hideTargets=b[this.hideElement],!this.hideTargets&&a&&Object.isString(a)&&(this.hideTargets=this.tip.select(a)),$w("show hide").each(function(b){var c=b.capitalize(),a=this.options[b+"On"].event||this.options[b+"On"];a=="mouseover"?a=="mouseenter":a=="mouseout"&&a=="mouseleave",this[b+"Action"]=a}.bind(this)),!this.eventToggle&&this.options.showOn&&this.element.observe(this.options.showOn,this.eventShow),this.hideTargets&&this.options.hideOn&&this.hideTargets.invoke("observe",this.hideAction,this.eventHide),!this.options.fixed&&this.options.showOn=="click"&&(this.eventPosition=this.position.bindAsEventListener(this),this.element.observe("mousemove",this.eventPosition)),this.buttonEvent=this.hide.wrap(function(c,a){var b=a.findElement(".close");b&&(b.blur(),a.stop(),c(a))}).bindAsEventListener(this),(this.closeButton||this.options.hideOn&&this.options.hideOn.element==".close")&&this.wrapper.observe("click",this.buttonEvent),this.options.showOn!="click"&&this.hideElement!="element"&&(this.eventCheckDelay=function(){this.clearTimer("show")}.bindAsEventListener(this),this.element.observe("mouseleave",this.eventCheckDelay)),(this.options.hideOn||this.options.hideAfter)&&(c=[this.element,this.wrapper],this.activityEnter=function(){Tips.raise(this),this.cancelHideAfter()}.bindAsEventListener(this),this.activityLeave=this.hideAfter.bindAsEventListener(this),c.invoke("observe","mouseenter",this.activityEnter).invoke("observe","mouseleave",this.activityLeave)),this.options.ajax&&this.options.showOn!="click"&&(this.ajaxHideEvent=this.ajaxHide.bindAsEventListener(this),this.element.observe("mouseleave",this.ajaxHideEvent))},deactivate:function(){this.options.showOn&&this.options.showOn==this.options.hideOn?this.element.stopObserving(this.options.showOn,this.eventToggle):(this.options.showOn&&this.element.stopObserving(this.options.showOn,this.eventShow),this.hideTargets&&this.options.hideOn&&this.hideTargets.invoke("stopObserving")),this.eventPosition&&this.element.stopObserving("mousemove",this.eventPosition),this.eventCheckDelay&&this.element.stopObserving("mouseout",this.eventCheckDelay),this.wrapper.stopObserving(),(this.options.hideOn||this.options.hideAfter)&&this.element.stopObserving("mouseenter",this.activityEnter).stopObserving("mouseleave",this.activityLeave),this.ajaxHideEvent&&this.element.stopObserving("mouseleave",this.ajaxHideEvent)},ajaxShow:function(e,a){var b,d,c;if(!this.tooltip){if(!this.build())return}if(this.position(a),this.ajaxContentLoading)return;if(this.ajaxContentLoaded){e(a);return}return this.ajaxContentLoading=!0,b={fakePointer:{pointerX:0,pointerY:0}},a.pointer?(d=a.pointer(),b={fakePointer:{pointerX:d.x,pointerY:d.y}}):a.fakePointer&&(b.fakePointer=a.fakePointer),c=Object.clone(this.options.ajax.options),c.onComplete=c.onComplete.wrap(function(c,a){this._update({title:this.options.title,content:a.responseText}),this.position(b),function(){c(a);var b=this.loader&&this.loader.visible();this.loader&&(this.clearTimer("loader"),this.loader.remove(),this.loader=null),b&&this.show(),this.ajaxContentLoaded=!0,this.ajaxContentLoading=null}.bind(this).delay(.6)}.bind(this)),this.loaderTimer=Element.show.delay(this.options.delay,this.loader),this.wrapper.hide(),this.ajaxContentLoading=!0,this.loader.show(),this.ajaxTimer=function(){new Ajax.Request(this.options.ajax.url,c)}.bind(this).delay(this.options.delay),!1},ajaxHide:function(){this.clearTimer("loader")},showDelayed:function(a){if(!this.tooltip){if(!this.build())return}if(this.position(a),this.wrapper.visible())return;this.clearTimer("show"),this.showTimer=this.show.bind(this).delay(this.options.delay)},clearTimer:function(a){this[a+"Timer"]&&clearTimeout(this[a+"Timer"])},show:function(){if(this.wrapper.visible())return;Tips.fixIE&&this.iframeShim.show(),this.options.hideOthers&&Tips.hideAll(),Tips.addVisibile(this),this.tooltip.show(),this.wrapper.show(),this.stem&&this.stem.show(),this.element.fire("prototip:shown")},hideAfter:function(a){if(this.options.ajax&&this.loader&&this.options.showOn!="click"&&this.loader.hide(),!this.options.hideAfter)return;this.cancelHideAfter(),this.hideAfterTimer=this.hide.bind(this).delay(this.options.hideAfter)},cancelHideAfter:function(){this.options.hideAfter&&this.clearTimer("hideAfter")},hide:function(){if(this.clearTimer("show"),this.clearTimer("loader"),!this.wrapper.visible())return;this.afterHide()},afterHide:function(){Tips.fixIE&&this.iframeShim.hide(),this.loader&&this.loader.hide(),this.wrapper.hide(),(this.borderFrame||this.tooltip).show(),Tips.removeVisible(this),this.element.fire("prototip:hidden")},toggle:function(a){this.wrapper&&this.wrapper.visible()?this.hide(a):this.showDelayed(a)},positionStem:function(){var a=this.options.stem,d=arguments[0]||this.stemInverse,c=Tips.inverseStem(a.position[0],d[a.orientation]),b=Tips.inverseStem(a.position[1],d[Tips._inverse[a.orientation]]),e=this.radius||0,f;this.stemImage.setPngBackground(this.images+c+b+".png"),a.orientation=="horizontal"?(f=c=="left"?a.height:0,this.stemWrapper.setStyle("left: "+f+"px;"),this.stemImage.setStyle({float:c}),this.stem.setStyle({left:0,top:b=="bottom"?"100%":b=="middle"?"50%":0,marginTop:(b=="bottom"?-1*a.width:b=="middle"?-.5*a.width:0)+(b=="bottom"?-1*e:b=="top"?e:0)+"px"})):(this.stemWrapper.setStyle(c=="top"?"margin: 0; padding: "+a.height+"px 0 0 0;":"padding: 0; margin: 0 0 "+a.height+"px 0;"),this.stem.setStyle(c=="top"?"top: 0; bottom: auto;":"top: auto; bottom: 0;"),this.stemImage.setStyle({margin:0,float:b!="middle"?b:"none"}),b=="middle"?this.stemImage.setStyle("margin: 0 auto;"):this.stemImage.setStyle("margin-"+b+": "+e+"px;"),Tips.WebKit419&&(c=="bottom"?(this.stem.setStyle({position:"relative",clear:"both",top:"auto",bottom:"auto",float:"left",width:"100%",margin:-1*a.height+"px 0 0 0"}),this.stem.style.display="block"):this.stem.setStyle({position:"absolute",float:"none",margin:0}))),this.stemInverse=d},position:function(e){var h,g,a,k,f,b,c,i,d,m,j,l;if(!this.tooltip){if(!this.build())return}if(Tips.raise(this),Tips.fixIE&&(h=this.wrapper.getDimensions(),(!this.iframeShimDimensions||this.iframeShimDimensions.height!=h.height||this.iframeShimDimensions.width!=h.width)&&this.iframeShim.setStyle({width:h.width+"px",height:h.height+"px"}),this.iframeShimDimensions=h),this.options.hook){if(this.mouseHook){switch(k=document.viewport.getScrollOffsets(),f=e.fakePointer||{},c=2,this.mouseHook.toUpperCase()){case"LEFTTOP":case"TOPLEFT":b={x:0-c,y:0-c};break;case"TOPMIDDLE":b={x:0,y:0-c};break;case"TOPRIGHT":case"RIGHTTOP":b={x:c,y:0-c};break;case"RIGHTMIDDLE":b={x:c,y:0};break;case"RIGHTBOTTOM":case"BOTTOMRIGHT":b={x:c,y:c};break;case"BOTTOMMIDDLE":b={x:0,y:c};break;case"BOTTOMLEFT":case"LEFTBOTTOM":b={x:0-c,y:c};break;case"LEFTMIDDLE":b={x:0-c,y:0};break}b.x+=this.options.offset.x,b.y+=this.options.offset.y,g=Object.extend({offset:b},{element:this.options.hook.tip,mouseHook:this.mouseHook,mouse:{top:f.pointerY||Event.pointerY(e)-k.top,left:f.pointerX||Event.pointerX(e)-k.left}}),a=Tips.hook(this.wrapper,this.target,g),this.options.viewport&&(i=this.getPositionWithinViewport(a),d=i.stemInverse,a=i.position,a.left+=d.vertical?2*Prototip.toggleInt(b.x-this.options.offset.x):0,a.top+=d.vertical?2*Prototip.toggleInt(b.y-this.options.offset.y):0,this.stem&&(this.stemInverse.horizontal!=d.horizontal||this.stemInverse.vertical!=d.vertical)&&this.positionStem(d)),a={left:a.left+"px",top:a.top+"px"},this.wrapper.setStyle(a)}else g=Object.extend({offset:this.options.offset},{element:this.options.hook.tip,target:this.options.hook.target}),a=Tips.hook(this.wrapper,this.target,Object.extend({position:!0},g)),a={left:a.left+"px",top:a.top+"px"};this.loader&&(m=Tips.hook(this.loader,this.target,Object.extend({position:!0},g))),Tips.fixIE&&this.iframeShim.setStyle(a)}else j=this.target.cumulativeOffset(),f=e.fakePointer||{},a={left:(this.options.fixed?j[0]:f.pointerX||Event.pointerX(e))+this.options.offset.x,top:(this.options.fixed?j[1]:f.pointerY||Event.pointerY(e))+this.options.offset.y},!this.options.fixed&&this.element!==this.target&&(l=this.element.cumulativeOffset(),a.left+=-1*(l[0]-j[0]),a.top+=-1*(l[1]-j[1])),!this.options.fixed&&this.options.viewport&&(i=this.getPositionWithinViewport(a),d=i.stemInverse,a=i.position,this.stem&&(this.stemInverse.horizontal!=d.horizontal||this.stemInverse.vertical!=d.vertical)&&this.positionStem(d)),a={left:a.left+"px",top:a.top+"px"},this.wrapper.setStyle(a),this.loader&&this.loader.setStyle(a),Tips.fixIE&&this.iframeShim.setStyle(a)},getPositionWithinViewport:function(c){var d={horizontal:!1,vertical:!1},e=this.wrapper.getDimensions(),f=document.viewport.getScrollOffsets(),g=document.viewport.getDimensions(),b={left:"width",top:"height"},a;for(a in b)c[a]+e[b[a]]-f[a]>g[b[a]]&&(c[a]=c[a]-(e[b[a]]+2*this.options.offset[a=="left"?"x":"y"]),this.stem&&(d[Tips._stemTranslation[b[a]]]=!0));return{position:c,stemInverse:d}}}),Object.extend(Prototip,{createCorner:function(h,f){var e=arguments[2]||this.options,b=e.radius,a=e.border,c={top:f.charAt(0)=="t",left:f.charAt(1)=="l"},i,d,j,g;this.support.canvas?(i=new Element("canvas",{className:"cornerCanvas"+f.capitalize(),width:a+"px",height:a+"px"}),h.insert(i),d=i.getContext("2d"),d.fillStyle=e.backgroundColor,d.arc(c.left?b:a-b,c.top?b:a-b,b,0,Math.PI*2,!0),d.fill(),d.fillRect(c.left?b:0,0,a-b,a),d.fillRect(0,c.top?b:0,a,a-b)):(h.insert(j=new Element("div").setStyle({width:a+"px",height:a+"px",margin:0,padding:0,display:"block",position:"relative",overflow:"hidden"})),g=new Element("ns_vml:roundrect",{fillcolor:e.backgroundColor,strokeWeight:"1px",strokeColor:e.backgroundColor,arcSize:(b/a*.5).toFixed(2)}).setStyle({width:2*a-1+"px",height:2*a-1+"px",position:"absolute",left:(c.left?0:-1*a)+"px",top:(c.top?0:-1*a)+"px"}),j.insert(g),g.outerHTML=g.outerHTML)}}),Element.addMethods({setPngBackground:function(a,c){a=$(a);var b=Object.extend({align:"top left",repeat:"no-repeat",sizingMethod:"scale",backgroundColor:""},arguments[2]||{});return a.setStyle(Tips.fixIE?{filter:"progid:DXImageTransform.Microsoft.AlphaImageLoader(src='"+c+"'', sizingMethod='"+b.sizingMethod+"')"}:{background:b.backgroundColor+" url("+c+") "+b.align+" "+b.repeat}),a}}),Prototip.Methods={hold:function(a){return!!(a.element&&!a.element.parentNode)},show:function(){var a,b,c,d;if(Prototip.Methods.hold(this))return;Tips.raise(this),this.cancelHideAfter(),a={},this.options.hook&&!this.options.hook.mouse?a.fakePointer={pointerX:0,pointerY:0}:(b=this.target.cumulativeOffset(),c=this.target.cumulativeScrollOffset(),d=document.viewport.getScrollOffsets(),b.left+=-1*(c[0]-d[0]),b.top+=-1*(c[1]-d[1]),a.fakePointer={pointerX:b.left,pointerY:b.top}),this.options.ajax&&!this.ajaxContentLoaded?this.ajaxShow(this.showDelayed,a):this.showDelayed(a),this.hideAfter()}},Prototip.extend=function(a){a.element.prototip={},Object.extend(a.element.prototip,{show:Prototip.Methods.show.bind(a),hide:a.hide.bind(a),remove:Tips.remove.bind(Tips,a.element)})},Prototip.start(),Validator=Class.create(),Validator.prototype={initialize:function(b,c,a,d){typeof a=='function'?(this.options=$H(d),this._test=a):(this.options=$H(a),this._test=function(){return!0}),this.error=c||'Validation failed.',this.className=b},test:function(a,b){return this._test(a,b)&&this.options.all(function(c){return!Validator.methods[c.key]||Validator.methods[c.key](a,b,c.value)})}},Validator.methods={pattern:function(a,c,b){return Validation.get('IsEmpty').test(a)||b.test(a)},minLength:function(a,c,b){return a.length>=b},maxLength:function(a,c,b){return a.length<=b},min:function(a,c,b){return a>=parseFloat(b)},max:function(a,c,b){return a<=parseFloat(b)},notOneOf:function(a,c,b){return $A(b).all(function(b){return a!=b})},oneOf:function(a,c,b){return $A(b).any(function(b){return a==b})},is:function(a,c,b){return a==b},isNot:function(a,c,b){return a!=b},equalToField:function(a,c,b){return a==$F(b)},notEqualToField:function(a,c,b){return a!=$F(b)},include:function(a,b,c){return $A(c).all(function(c){return Validation.get(c).test(a,b)})}},Validation=Class.create(),Validation.prototype={initialize:function(c,d){var a,b;this.options=Object.extend({onSubmit:!0,stopOnFirst:!1,immediate:!1,focusOnError:!0,useTitles:!1,onFormValidate:function(a,b){},onElementValidate:function(a,b){}},d||{}),this.form=$(c),this.options.onSubmit&&Event.observe(this.form,'submit',this.onSubmit.bind(this),!1),this.options.immediate&&(a=this.options.useTitles,b=this.options.onElementValidate,Form.getElements(this.form).each(function(c){Event.observe(c,'blur',function(c){Validation.validate(Event.element(c),{useTitle:a,onElementValidate:b})})}))},onSubmit:function(a){this.validate()||Event.stop(a)},validate:function(){var a=!1,b=this.options.useTitles,c=this.options.onElementValidate;return this.options.stopOnFirst?a=Form.getElements(this.form).all(function(a){return Validation.validate(a,{useTitle:b,onElementValidate:c})}):a=Form.getElements(this.form).collect(function(a){return Validation.validate(a,{useTitle:b,onElementValidate:c})}).all(),!a&&this.options.focusOnError&&Form.getElements(this.form).findAll(function(a){return $(a).hasClassName('validation-failed')}).first().focus(),this.options.onFormValidate(a,this.form),a},reset:function(){Form.getElements(this.form).each(Validation.reset)}},Object.extend(Validation,{validate:function(a,b){b=Object.extend({useTitle:!1,onElementValidate:function(a,b){}},b||{}),a=$(a);var c=a.classNames();return result=c.all(function(d){var c=Validation.test(d,a,b.useTitle);return b.onElementValidate(c,a),c})},test:function(c,a,g){var d=Validation.get(c),e='__advice'+c.camelize(),b,h,f;try{if(Validation.isVisible(a)&&!d.test($F(a),a)){if(!a[e]){if(b=Validation.getAdvice(c,a),b==null){switch(h=g?a&&a.title?a.title:d.error:d.error,b='<div class="validation-advice" id="advice-'+c+'-'+Validation.getElmID(a)+'" style="display:none">'+h+'</div>',a.type.toLowerCase()){case'checkbox':case'radio':f=a.parentNode,f?new Insertion.Bottom(f,b):new Insertion.After(a,b);break;default:new Insertion.After(a,b)}b=Validation.getAdvice(c,a)}typeof Effect=='undefined'?b.style.display='block':new Effect.Appear(b,{duration:1})}return a[e]=!0,a.removeClassName('validation-passed'),a.addClassName('validation-failed'),!1}return b=Validation.getAdvice(c,a),b!=null&&b.hide(),a[e]='',a.removeClassName('validation-failed'),a.addClassName('validation-passed'),!0}catch(a){throw a}},isVisible:function(a){while(a.tagName!='BODY'){if(!$(a).visible())return!1;a=a.parentNode}return!0},getAdvice:function(b,a){return $('advice-'+b+'-'+Validation.getElmID(a))||$('advice-'+Validation.getElmID(a))},getElmID:function(a){return a.id?a.id:a.name},reset:function(a){a=$(a);var b=a.classNames();b.each(function(b){var c='__advice'+b.camelize(),d;a[c]&&(d=Validation.getAdvice(b,a),d.hide(),a[c]=''),a.removeClassName('validation-failed'),a.removeClassName('validation-passed')})},add:function(a,c,d,e){var b={};b[a]=new Validator(a,c,d,e),Object.extend(Validation.methods,b)},addAllThese:function(b){var a={};$A(b).each(function(b){a[b[0]]=new Validator(b[0],b[1],b[2],b.length>3?b[3]:{})}),Object.extend(Validation.methods,a)},get:function(a){return Validation.methods[a]?Validation.methods[a]:Validation.methods._LikeNoIDIEverSaw_},methods:{_LikeNoIDIEverSaw_:new Validator('_LikeNoIDIEverSaw_','',{})}}),Validation.add('IsEmpty','',function(a){return a==null||a.length==0}),Validation.addAllThese([['required','This is a required field.',function(a){return!Validation.get('IsEmpty').test(a)}],['validate-number','Please enter a valid number in this field.',function(a){return Validation.get('IsEmpty').test(a)||!isNaN(a)&&!/^\s+$/.test(a)}],['validate-digits','Please use numbers only in this field. please avoid spaces or other characters such as dots or commas.',function(a){return Validation.get('IsEmpty').test(a)||!/[^\d]/.test(a)}],['validate-alpha','Please use letters only (a-z) in this field.',function(a){return Validation.get('IsEmpty').test(a)||/^[a-zA-Z]+$/.test(a)}],['validate-alphanum','Please use only letters (a-z) or numbers (0-9) only in this field. No spaces or other characters are allowed.',function(a){return Validation.get('IsEmpty').test(a)||!/\W/.test(a)}],['validate-date','Please enter a valid date.',function(a){var b=new Date(a);return Validation.get('IsEmpty').test(a)||!isNaN(b)}],['validate-email','Please enter a valid email address. For example fred@domain.com .',function(a){return Validation.get('IsEmpty').test(a)||/\w{1,}[@][\w\-]{1,}([.]([\w\-]{1,})){1,3}$/.test(a)}],['validate-url','Please enter a valid URL.',function(a){return Validation.get('IsEmpty').test(a)||/^(http|https|ftp):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(:(\d+))?\/?/i.test(a)}],['validate-date-au','Please use this date format: dd/mm/yyyy. For example 17/03/2006 for the 17th of March, 2006.',function(b){var c,a;return!!Validation.get('IsEmpty').test(b)||(!(c=/^(\d{2})\/(\d{2})\/(\d{4})$/,!c.test(b))&&(a=new Date(b.replace(c,'$2/$1/$3')),parseInt(RegExp.$2,10)==1+a.getMonth()&&parseInt(RegExp.$1,10)==a.getDate()&&parseInt(RegExp.$3,10)==a.getFullYear()))}],['validate-currency-dollar','Please enter a valid $ amount. For example $100.00 .',function(a){return Validation.get('IsEmpty').test(a)||/^\$?\-?([1-9]{1}[0-9]{0,2}(\,[0-9]{3})*(\.[0-9]{0,2})?|[1-9]{1}\d*(\.[0-9]{0,2})?|0(\.[0-9]{0,2})?|(\.[0-9]{1,2})?)$/.test(a)}],['validate-selection','Please make a selection',function(b,a){return a.options?a.selectedIndex>0:!Validation.get('IsEmpty').test(b)}],['validate-one-required','Please select one of the above options.',function(d,a){var b=a.parentNode,c=b.getElementsByTagName('INPUT');return $A(c).any(function(a){return $F(a)})}]]),typeof Control=='undefined'&&(Control={}),$proc=function(a){return typeof a=='function'?a:function(){return a}},$value=function(a){return typeof a=='function'?a():a},Object.Event={extend:function(a){a._objectEventSetup=function(a){this._observers=this._observers||{},this._observers[a]=this._observers[a]||[]},a.observe=function(a,b){if(typeof a=='string'&&typeof b!='undefined')this._objectEventSetup(a),this._observers[a].include(b)||this._observers[a].push(b);else for(var c in a)this.observe(c,a[c])},a.stopObserving=function(a,b){this._objectEventSetup(a),a&&b?this._observers[a]=this._observers[a].without(b):a?this._observers[a]=[]:this._observers={}},a.observeOnce=function(a,c){var b=function(){c.apply(this,arguments),this.stopObserving(a,b)}.bind(this);this._objectEventSetup(a),this._observers[a].push(b)},a.notify=function(b){var c,d,a;this._objectEventSetup(b),c=[],d=$A(arguments).slice(1);try{for(a=0;a<this._observers[b].length;++a)c.push(this._observers[b][a].apply(this._observers[b][a],d)||null)}catch(a){if(a==$break)return!1;throw a}return c},a.prototype&&(a.prototype._objectEventSetup=a._objectEventSetup,a.prototype.observe=a.observe,a.prototype.stopObserving=a.stopObserving,a.prototype.observeOnce=a.observeOnce,a.prototype.notify=function(c){var b,e,f,d;a.notify&&(b=$A(arguments).slice(1),b.unshift(this),b.unshift(c),a.notify.apply(a,b)),this._objectEventSetup(c),b=$A(arguments).slice(1),e=[];try{this.options&&this.options[c]&&typeof this.options[c]=='function'&&e.push(this.options[c].apply(this,b)||null),f=this._observers[c];for(d=0;d<f.length;++d)e.push(f[d].apply(f[d],b)||null)}catch(a){if(a==$break)return!1;throw a}return e})}},Element.addMethods({observeOnce:function(a,b,d){var c=function(){d.apply(this,arguments),Element.stopObserving(a,b,c)};Element.observe(a,b,c)}}),function(){function a(a){var c,b,d;if(a.wheelDelta?c=a.wheelDelta/120:a.detail&&(c=-a.detail/3),!c)return;if(b=Event.extend(a).target,b=Element.extend(b.nodeType===Node.TEXT_NODE?b.parentNode:b),d=b.fire('mouse:wheel',{delta:c}),d.stopped)return Event.stop(a),!1}document.observe('mousewheel',a),document.observe('DOMMouseScroll',a)}(),IframeShim=Class.create({initialize:function(){this.element=new Element('iframe',{style:'position:absolute;filter:progid:DXImageTransform.Microsoft.Alpha(opacity=0);display:none',src:'javascript:void(0);',frameborder:0}),$(document.body).insert(this.element)},hide:function(){return this.element.hide(),this},show:function(){return this.element.show(),this},positionUnder:function(a){var a=$(a),b=a.cumulativeOffset(),c=a.getDimensions();return this.element.setStyle({left:b[0]+'px',top:b[1]+'px',width:c.width+'px',height:c.height+'px',zIndex:a.getStyle('zIndex')-1}).show(),this},setBounds:function(a){for(prop in a)a[prop]+='px';return this.element.setStyle(a),this},destroy:function(){return this.element&&this.element.remove(),this}}),typeof Prototype=="undefined")throw"Control.Tabs requires Prototype to be loaded.";if(typeof Object.Event=="undefined")throw"Control.Tabs requires Object.Event to be loaded.";Control.Tabs=Class.create({initialize:function(a,c){if(!$(a))throw"Control.Tabs could not find the element: "+a;this.activeContainer=!1,this.activeLink=!1,this.containers=$H({}),this.links=[],Control.Tabs.instances.push(this),this.options={beforeChange:Prototype.emptyFunction,afterChange:Prototype.emptyFunction,hover:!1,linkSelector:'li a',setClassOnContainer:!1,activeClassName:'active',defaultTab:'first',autoLinkExternal:!0,targetRegExp:/#(.+)$/,showFunction:Element.show,hideFunction:Element.hide},Object.extend(this.options,c||{}),(typeof this.options.linkSelector=='string'?$(a).select(this.options.linkSelector):this.options.linkSelector($(a))).findAll(function(a){return/^#/.exec(a.href.replace(window.location.href.split('#')[0],''))}).each(function(a){this.addTab(a)}.bind(this)),this.containers.values().each(Element.hide),this.options.defaultTab=='first'?this.setActiveTab(this.links.first()):this.options.defaultTab=='last'?this.setActiveTab(this.links.last()):this.setActiveTab(this.options.defaultTab);var b=this.options.targetRegExp.exec(window.location);b&&b[1]&&b[1].split(',').each(function(a){this.setActiveTab(this.links.find(function(b){return b.key==a}))}.bind(this)),this.options.autoLinkExternal&&$A(document.getElementsByTagName('a')).each(function(a){if(!this.links.include(a)){var b=a.href.replace(window.location.href.split('#')[0],'');b.substring(0,1)=='#'&&this.containers.keys().include(b.substring(1))&&$(a).observe('click',function(b,a){this.setActiveTab(a.substring(1))}.bindAsEventListener(this,b))}}.bind(this))},addTab:function(a){this.links.push(a),a.key=a.getAttribute('href').replace(window.location.href.split('#')[0],'').split('#').last().replace(/#/,'');var b=$(a.key);if(!b)throw"Control.Tabs: #"+a.key+" was not found on the page.";this.containers.set(a.key,b),a[this.options.hover?'onmouseover':'onclick']=function(a){return window.event&&Event.stop(window.event),this.setActiveTab(a),!1}.bind(this,a)},setActiveTab:function(a){if(!a&&typeof a=='undefined')return;if(typeof a=='string')this.setActiveTab(this.links.find(function(b){return b.key==a}));else if(typeof a=='number')this.setActiveTab(this.links[a]);else{if(this.notify('beforeChange',this.activeContainer,this.containers.get(a.key))===!1)return;this.activeContainer&&this.options.hideFunction(this.activeContainer),this.links.each(function(a){(this.options.setClassOnContainer?$(a.parentNode):a).removeClassName(this.options.activeClassName)}.bind(this)),(this.options.setClassOnContainer?$(a.parentNode):a).addClassName(this.options.activeClassName),this.activeContainer=this.containers.get(a.key),this.activeLink=a,this.options.showFunction(this.containers.get(a.key)),this.notify('afterChange',this.containers.get(a.key))}},next:function(){this.links.each(function(b,a){if(this.activeLink==b&&this.links[a+1])throw this.setActiveTab(this.links[a+1]),$break}.bind(this))},previous:function(){this.links.each(function(b,a){if(this.activeLink==b&&this.links[a-1])throw this.setActiveTab(this.links[a-1]),$break}.bind(this))},first:function(){this.setActiveTab(this.links.first())},last:function(){this.setActiveTab(this.links.last())}}),Object.extend(Control.Tabs,{instances:[],findByTabId:function(a){return Control.Tabs.instances.find(function(b){return b.links.find(function(b){return b.key==a})})}}),Object.Event.extend(Control.Tabs),dynamicOptionListCount=0,dynamicOptionListObjects=new Array;function initDynamicOptionLists(){for(var h=0,a,i,f,d,e,c,b,j,k,g;h<dynamicOptionListObjects.length;h++){if(a=dynamicOptionListObjects[h],a.formName!=null)a.form=document.forms[a.formName];else if(a.formIndex!=null)a.form=document.forms[a.formIndex];else{i=a.fieldNames[0][0];for(f=0;f<document.forms.length;f++)if(typeof document.forms[f][i]!="undefined"){a.form=document.forms[f];break}if(a.form==null){alert("ERROR: Couldn't find form element "+i+" in any form on the page! Init aborted");return}}for(d=0;d<a.fieldNames.length;d++)for(e=0;e<a.fieldNames[d].length-1;e++){if(c=a.form[a.fieldNames[d][e]],typeof c=="undefined"){alert("Select box named "+a.fieldNames[d][e]+" could not be found in the form. Init aborted");return}if(e==0){if(c.options!=null)for(l=0;l<c.options.length;l++)b=c.options[l],j=a.findMatchingOptionInArray(a.options,b.text,b.value,!1),j!=null&&(k=b.selected,g=new Option(b.text,b.value,b.defaultSelected,b.selected),g.selected=b.selected,g.defaultSelected=b.defaultSelected,g.DOLOption=j,c.options[l]=g,c.options[l].selected=k)}c.onchange==null&&(c.onchange=new Function("dynamicOptionListObjects["+a.index+"].change(this)"))}}resetDynamicOptionLists()}function resetDynamicOptionLists(b){for(var c=0,a,d;c<dynamicOptionListObjects.length;c++)if(a=dynamicOptionListObjects[c],typeof b=="undefined"||b==null||b==a.form)for(d=0;d<a.fieldNames.length;d++)a.change(a.form[a.fieldNames[d][0]],!0)}function DOLOption(a,b,c,d){return this.text=a,this.value=b,this.defaultSelected=c,this.selected=d,this.options=new Array,this}function DynamicOptionList(){if(this.form=null,this.options=new Array,this.longestString=new Array,this.numberOfOptions=new Array,this.currentNode=null,this.currentField=null,this.currentNodeDepth=0,this.fieldNames=new Array,this.formIndex=null,this.formName=null,this.fieldListIndexes=new Object,this.fieldIndexes=new Object,this.selectFirstOption=!0,this.numberOfOptions=new Array,this.longestString=new Array,this.values=new Object,this.forValue=DOL_forValue,this.forText=DOL_forText,this.forField=DOL_forField,this.forX=DOL_forX,this.addOptions=DOL_addOptions,this.addOptionsTextValue=DOL_addOptionsTextValue,this.setDefaultOptions=DOL_setDefaultOptions,this.setValues=DOL_setValues,this.setValue=DOL_setValues,this.setFormIndex=DOL_setFormIndex,this.setFormName=DOL_setFormName,this.printOptions=DOL_printOptions,this.addDependentFields=DOL_addDependentFields,this.change=DOL_change,this.child=DOL_child,this.selectChildOptions=DOL_selectChildOptions,this.populateChild=DOL_populateChild,this.change=DOL_change,this.addNewOptionToList=DOL_addNewOptionToList,this.findMatchingOptionInArray=DOL_findMatchingOptionInArray,arguments.length>0){for(var a=0;a<arguments.length;a++)this.fieldListIndexes[arguments[a].toString()]=this.fieldNames.length,this.fieldIndexes[arguments[a].toString()]=a;this.fieldNames[this.fieldNames.length]=arguments}this.index=window.dynamicOptionListCount++,window.dynamicOptionListObjects[this.index]=this}function DOL_findMatchingOptionInArray(c,g,f,h){var b,d,e,a;if(c==null||typeof c=="undefined")return null;b=null,d=null;for(e=0;e<c.length;e++){if(a=c[e],a.value==f&&a.text==g)return a;h||(b==null&&f!=null&&a.value==f&&(b=a),d==null&&g!=null&&a.text==g&&(d=a))}return b!=null?b:d}function DOL_forX(c,d){var b,a;return this.currentNode==null&&(this.currentNodeDepth=0),b=this.currentNode==null?this:this.currentNode,a=this.findMatchingOptionInArray(b.options,d=="text"?c:null,d=="value"?c:null,!1),a==null&&(a=new DOLOption(null,null,!1,!1),a[d]=c,b.options[b.options.length]=a),this.currentNode=a,this.currentNodeDepth++,this}function DOL_forValue(a){return this.forX(a,"value")}function DOL_forText(a){return this.forX(a,"text")}function DOL_forField(a){return this.currentField=a,this}function DOL_addNewOptionToList(a,d,e,f){var c=new DOLOption(d,e,f,!1),b;a==null&&(a=new Array);for(b=0;b<a.length;b++)if(a[b].text==c.text&&a[b].value==c.value)return c.selected&&(a[b].selected=!0),c.defaultSelected&&(a[b].defaultSelected=!0),a;a[a.length]=c}function DOL_addOptions(){var b,a;this.currentNode==null&&(this.currentNode=this),this.currentNode.options==null&&(this.currentNode.options=new Array);for(b=0;b<arguments.length;b++)a=arguments[b],this.addNewOptionToList(this.currentNode.options,a,a,!1),typeof this.numberOfOptions[this.currentNodeDepth]=="undefined"&&(this.numberOfOptions[this.currentNodeDepth]=0),this.currentNode.options.length>this.numberOfOptions[this.currentNodeDepth]&&(this.numberOfOptions[this.currentNodeDepth]=this.currentNode.options.length),(typeof this.longestString[this.currentNodeDepth]=="undefined"||a.length>this.longestString[this.currentNodeDepth].length)&&(this.longestString[this.currentNodeDepth]=a);this.currentNode=null,this.currentNodeDepth=0}function DOL_addOptionsTextValue(){var a,b,c;this.currentNode==null&&(this.currentNode=this),this.currentNode.options==null&&(this.currentNode.options=new Array);for(a=0;a<arguments.length;a++)b=arguments[a++],c=arguments[a],this.addNewOptionToList(this.currentNode.options,b,c,!1),typeof this.numberOfOptions[this.currentNodeDepth]=="undefined"&&(this.numberOfOptions[this.currentNodeDepth]=0),this.currentNode.options.length>this.numberOfOptions[this.currentNodeDepth]&&(this.numberOfOptions[this.currentNodeDepth]=this.currentNode.options.length),(typeof this.longestString[this.currentNodeDepth]=="undefined"||b.length>this.longestString[this.currentNodeDepth].length)&&(this.longestString[this.currentNodeDepth]=b);this.currentNode=null,this.currentNodeDepth=0}function DOL_child(a){var b=this.fieldListIndexes[a.name],c=this.fieldIndexes[a.name];return c<this.fieldNames[b].length-1?this.form[this.fieldNames[b][c+1]]:null}function DOL_setDefaultOptions(){var a,b;this.currentNode==null&&(this.currentNode=this);for(a=0;a<arguments.length;a++)b=this.findMatchingOptionInArray(this.currentNode.options,null,arguments[a],!1),b!=null&&(b.defaultSelected=!0);this.currentNode=null}function DOL_setValues(){if(this.currentField==null){alert("Can't call setValues() without using forField() first!");return}typeof this.values[this.currentField]=="undefined"&&(this.values[this.currentField]=new Object);for(var a=0;a<arguments.length;a++)this.values[this.currentField][arguments[a]]=!0;this.currentField=null}function DOL_setFormIndex(a){this.formIndex=a}function DOL_setFormName(a){this.formName=a}function DOL_printOptions(d){var b,c,a;if(navigator.appName=='Netscape'&&parseInt(navigator.appVersion)<=4){if(b=this.fieldIndexes[d],c="",typeof this.numberOfOptions[b]!="undefined")for(a=0;a<this.numberOfOptions[b];a++)c+="<OPTION>";if(c+="<OPTION>",typeof this.longestString[b]!="undefined")for(a=0;a<this.longestString[b].length;a++)c+="_";document.writeln(c)}}function DOL_addDependentFields(){for(var a=0;a<arguments.length;a++)this.fieldListIndexes[arguments[a].toString()]=this.fieldNames.length,this.fieldIndexes[arguments[a].toString()]=a;this.fieldNames[this.fieldNames.length]=arguments}function DOL_change(c,d){var k,l,a,i,g,b,e,f,h,j;if((d==null||typeof d=="undefined")&&(d=!1),k=this.fieldListIndexes[c.name],l=this.fieldIndexes[c.name],a=this.child(c),a==null)return;if(c.type=="select-one")a.options!=null&&(a.options.length=0),c.options!=null&&c.options.length>0&&c.selectedIndex>=0&&(i=c.options[c.selectedIndex],this.populateChild(i.DOLOption,a,d),this.selectChildOptions(a,d));else if(c.type=="select-multiple"){if(g=new Array,!d)for(b=0;b<a.options.length;b++)e=a.options[b],e.selected&&this.addNewOptionToList(g,e.text,e.value,e.defaultSelected);if(a.options.length=0,c.options!=null){f=c.options;for(b=0;b<f.length;b++)f[b].selected&&this.populateChild(f[b].DOLOption,a,d);if(h=!1,!d)for(b=0;b<a.options.length;b++)j=this.findMatchingOptionInArray(g,a.options[b].text,a.options[b].value,!0),j!=null&&(a.options[b].selected=!0,h=!0);h||this.selectChildOptions(a,d)}}this.change(a,d)}function DOL_populateChild(d,a,j){var f,b,h,i,e,g,c;if(d!=null&&d.options!=null)for(f=0;f<d.options.length;f++){b=d.options[f],a.options==null&&(a.options=new Array),h=!1,i=!1;for(e=0;e<a.options.length;e++)if(g=a.options[e],g.text==b.text&&g.value==b.value){h=!0;break}h||(c=new Option(b.text,b.value,!1,!1),c.selected=!1,c.defaultSelected=!1,c.DOLOption=b,a.options[a.options.length]=c)}}function DOL_selectChildOptions(a,h){var d=this.values[a.name],f=!1,c,g,e,b;if(h&&d!=null&&typeof d!="undefined")for(c=0;c<a.options.length;c++)if(g=a.options[c].value,g!=null&&d[g]!=null&&typeof d[g]!="undefined"){f=!0;break}e=!1;for(c=0;c<a.options.length;c++)b=a.options[c],f&&b.value!=null&&d[b.value]!=null&&typeof d[b.value]!="undefined"?(b.selected=!0,e=!0):!f&&b.DOLOption!=null&&b.DOLOption.defaultSelected&&(b.selected=!0,e=!0);this.selectFirstOption&&!e&&a.options.length>0?a.options[0].selected=!0:!e&&a.type=="select-one"&&(a.selectedIndex=-1)}"use strict",window.jscolor||(window.jscolor=function(){var a={register:function(){a.attachDOMReadyEvent(a.init),a.attachEvent(document,"mousedown",a.onDocumentMouseDown),a.attachEvent(document,"touchstart",a.onDocumentTouchStart),a.attachEvent(window,"resize",a.onWindowResize)},init:function(){a.jscolor.lookupClass&&a.jscolor.installByClassName(a.jscolor.lookupClass)},tryInstallOnElements:function(c,i){for(var j=new RegExp("(^|\\s)("+i+")(\\s*(\\{[^}]*\\})|\\s|$)","i"),b=0,e,f,d,g,h;b<c.length;b+=1){if(c[b].type!==void 0&&c[b].type.toLowerCase()=="color"&&a.isColorAttrSupported)continue;if(!c[b].jscolor&&c[b].className&&(e=c[b].className.match(j))){if(f=c[b],d=null,g=a.getDataAttr(f,"jscolor"),g!==null?d=g:e[4]&&(d=e[4]),h={},d)try{h=new Function("return ("+d+")")()}catch(b){a.warn("Error parsing jscolor options: "+b+":\n"+d)}f.jscolor=new a.jscolor(f,h)}}},isColorAttrSupported:function(){var a=document.createElement("input");if(a.setAttribute){if(a.setAttribute("type","color"),a.type.toLowerCase()=="color")return!0}return!1}(),isCanvasSupported:function(){var a=document.createElement("canvas");return!!a.getContext&&!!a.getContext("2d")}(),fetchElement:function(a){return typeof a=="string"?document.getElementById(a):a},isElementType:function(a,b){return a.nodeName.toLowerCase()===b.toLowerCase()},getDataAttr:function(b,c){var d="data-"+c,a=b.getAttribute(d);return a!==null?a:null},attachEvent:function(a,b,c){a.addEventListener?a.addEventListener(b,c,!1):a.attachEvent&&a.attachEvent("on"+b,c)},detachEvent:function(a,b,c){a.removeEventListener?a.removeEventListener(b,c,!1):a.detachEvent&&a.detachEvent("on"+b,c)},_attachedGroupEvents:{},attachGroupEvent:function(b,c,d,e){a._attachedGroupEvents.hasOwnProperty(b)||(a._attachedGroupEvents[b]=[]),a._attachedGroupEvents[b].push([c,d,e]),a.attachEvent(c,d,e)},detachGroupEvents:function(b){var c,d;if(a._attachedGroupEvents.hasOwnProperty(b)){for(c=0;c<a._attachedGroupEvents[b].length;c+=1)d=a._attachedGroupEvents[b][c],a.detachEvent(d[0],d[1],d[2]);delete a._attachedGroupEvents[b]}},attachDOMReadyEvent:function(d){var b=!1,a=function(){b||(b=!0,d())},c;if(document.readyState==="complete"){setTimeout(a,1);return}document.addEventListener?(document.addEventListener("DOMContentLoaded",a,!1),window.addEventListener("load",a,!1)):document.attachEvent&&(document.attachEvent("onreadystatechange",function(){document.readyState==="complete"&&(document.detachEvent("onreadystatechange",arguments.callee),a())}),window.attachEvent("onload",a),document.documentElement.doScroll&&window==window.top&&(c=function(){if(!document.body)return;try{document.documentElement.doScroll("left"),a()}catch(a){setTimeout(c,1)}},c()))},warn:function(a){window.console&&window.console.warn&&window.console.warn(a)},preventDefault:function(a){a.preventDefault&&a.preventDefault(),a.returnValue=!1},captureTarget:function(b){b.setCapture&&(a._capturedTarget=b,a._capturedTarget.setCapture())},releaseTarget:function(){a._capturedTarget&&(a._capturedTarget.releaseCapture(),a._capturedTarget=null)},fireEvent:function(a,c){var b;if(!a)return;document.createEvent?(b=document.createEvent("HTMLEvents"),b.initEvent(c,!0,!0),a.dispatchEvent(b)):document.createEventObject?(b=document.createEventObject(),a.fireEvent("on"+c,b)):a["on"+c]&&a["on"+c]()},classNameToList:function(a){return a.replace(/^\s+|\s+$/g,"").split(/\s+/)},hasClass:function(b,a){return!!a&&-1!=(" "+b.className.replace(/\s+/g," ")+" ").indexOf(" "+a+" ")},setClass:function(c,e){for(var d=a.classNameToList(e),b=0;b<d.length;b+=1)a.hasClass(c,d[b])||(c.className+=(c.className?" ":"")+d[b])},unsetClass:function(d,e){for(var c=a.classNameToList(e),b=0,f;b<c.length;b+=1)f=new RegExp("^\\s*"+c[b]+"\\s*|"+"\\s*"+c[b]+"\\s*$|"+"\\s+"+c[b]+"(\\s+)","g"),d.className=d.className.replace(f,"$1")},getStyle:function(a){return window.getComputedStyle?window.getComputedStyle(a):a.currentStyle},setStyle:function(){var b=document.createElement("div"),a=function(c){for(var a=0;a<c.length;a+=1)if(c[a]in b.style)return c[a]},c={borderRadius:a(["borderRadius","MozBorderRadius","webkitBorderRadius"]),boxShadow:a(["boxShadow","MozBoxShadow","webkitBoxShadow"])};return function(a,d,b){switch(d.toLowerCase()){case"opacity":var e=Math.round(parseFloat(b)*100);a.style.opacity=b,a.style.filter="alpha(opacity="+e+")";break;default:a.style[c[d]]=b}}}(),setBorderRadius:function(b,c){a.setStyle(b,"borderRadius",c||"0")},setBoxShadow:function(b,c){a.setStyle(b,"boxShadow",c||"none")},getElementPos:function(f,g){var b=0,c=0,d=f.getBoundingClientRect(),e;return b=d.left,c=d.top,g||(e=a.getViewPos(),b+=e[0],c+=e[1]),[b,c]},getElementSize:function(a){return[a.offsetWidth,a.offsetHeight]},getAbsPointerPos:function(a){a||(a=window.event);var b=0,c=0;return typeof a.changedTouches!="undefined"&&a.changedTouches.length?(b=a.changedTouches[0].clientX,c=a.changedTouches[0].clientY):typeof a.clientX=="number"&&(b=a.clientX,c=a.clientY),{x:b,y:c}},getRelPointerPos:function(a){a||(a=window.event);var g=a.target||a.srcElement,d=g.getBoundingClientRect(),e=0,f=0,b=0,c=0;return typeof a.changedTouches!="undefined"&&a.changedTouches.length?(b=a.changedTouches[0].clientX,c=a.changedTouches[0].clientY):typeof a.clientX=="number"&&(b=a.clientX,c=a.clientY),e=b-d.left,f=c-d.top,{x:e,y:f}},getViewPos:function(){var a=document.documentElement;return[(window.pageXOffset||a.scrollLeft)-(a.clientLeft||0),(window.pageYOffset||a.scrollTop)-(a.clientTop||0)]},getViewSize:function(){var a=document.documentElement;return[window.innerWidth||a.clientWidth,window.innerHeight||a.clientHeight]},redrawPosition:function(){var h,d,j,f,l,i,c,b,g,e,k,m,n,o,p;if(a.picker&&a.picker.owner){switch(h=a.picker.owner,h.fixed?(d=a.getElementPos(h.targetElement,!0),j=[0,0]):(d=a.getElementPos(h.targetElement),j=a.getViewPos()),f=a.getElementSize(h.targetElement),l=a.getViewSize(),i=a.getPickerOuterDims(h),h.position.toLowerCase()){case"left":c=1,b=0,g=-1;break;case"right":c=1,b=0,g=1;break;case"top":c=0,b=1,g=-1;break;default:c=0,b=1,g=1}e=(f[b]+i[b])/2,h.smartPosition?(k=[-j[c]+d[c]+i[c]>l[c]?-j[c]+d[c]+f[c]/2>l[c]/2&&d[c]+f[c]-i[c]>=0?d[c]+f[c]-i[c]:d[c]:d[c],-j[b]+d[b]+f[b]+i[b]-e+e*g>l[b]?-j[b]+d[b]+f[b]/2>l[b]/2&&d[b]+f[b]-e-e*g>=0?d[b]+f[b]-e-e*g:d[b]+f[b]-e+e*g:d[b]+f[b]-e+e*g>=0?d[b]+f[b]-e+e*g:d[b]+f[b]-e-e*g]):(k=[d[c],d[b]+f[b]-e+e*g]),m=k[c],n=k[b],o=h.fixed?"fixed":"absolute",p=(k[0]+i[0]>d[0]||k[0]<d[0]+f[0])&&k[1]+i[1]<d[1]+f[1],a._drawPosition(h,m,n,o,p)}},_drawPosition:function(b,c,d,e,f){var g=f?0:b.shadowBlur;a.picker.wrap.style.position=e,a.picker.wrap.style.left=c+"px",a.picker.wrap.style.top=d+"px",a.setBoxShadow(a.picker.boxS,b.shadow?new a.BoxShadow(0,g,b.shadowBlur,0,b.shadowColor):null)},getPickerDims:function(b){var c=!!a.getSliderComponent(b),d=[2*b.insetWidth+2*b.padding+b.width+(c?2*b.insetWidth+a.getPadToSliderPadding(b)+b.sliderSize:0),2*b.insetWidth+2*b.padding+b.height+(b.closable?2*b.insetWidth+b.padding+b.buttonHeight:0)];return d},getPickerOuterDims:function(b){var c=a.getPickerDims(b);return[c[0]+2*b.borderWidth,c[1]+2*b.borderWidth]},getPadToSliderPadding:function(a){return Math.max(a.padding,1.5*(2*a.pointerBorderWidth+a.pointerThickness))},getPadYComponent:function(a){switch(a.mode.charAt(1).toLowerCase()){case"v":return"v"}return"s"},getSliderComponent:function(a){if(a.mode.length>2)switch(a.mode.charAt(2).toLowerCase()){case"s":return"s";case"v":return"v"}return null},onDocumentMouseDown:function(c){c||(c=window.event);var b=c.target||c.srcElement;b._jscLinkedInstance?b._jscLinkedInstance.showOnClick&&b._jscLinkedInstance.show():b._jscControlName?a.onControlPointerStart(c,b,b._jscControlName,"mouse"):a.picker&&a.picker.owner&&a.picker.owner.hide()},onDocumentTouchStart:function(c){c||(c=window.event);var b=c.target||c.srcElement;b._jscLinkedInstance?b._jscLinkedInstance.showOnClick&&b._jscLinkedInstance.show():b._jscControlName?a.onControlPointerStart(c,b,b._jscControlName,"touch"):a.picker&&a.picker.owner&&a.picker.owner.hide()},onWindowResize:function(b){a.redrawPosition()},onParentScroll:function(b){a.picker&&a.picker.owner&&a.picker.owner.hide()},_pointerMoveEvent:{mouse:"mousemove",touch:"touchmove"},_pointerEndEvent:{mouse:"mouseup",touch:"touchend"},_pointerOrigin:null,_capturedTarget:null,onControlPointerStart:function(c,d,f,e){var b=d._jscInstance,g,h,k,i,j;switch(a.preventDefault(c),a.captureTarget(d),g=function(b,g){a.attachGroupEvent("drag",b,a._pointerMoveEvent[e],a.onDocumentPointerMove(c,d,f,e,g)),a.attachGroupEvent("drag",b,a._pointerEndEvent[e],a.onDocumentPointerEnd(c,d,f,e))},g(document,[0,0]),window.parent&&window.frameElement&&(h=window.frameElement.getBoundingClientRect(),k=[-h.left,-h.top],g(window.parent.window.document,k)),i=a.getAbsPointerPos(c),j=a.getRelPointerPos(c),a._pointerOrigin={x:i.x-j.x,y:i.y-j.y},f){case"pad":switch(a.getSliderComponent(b)){case"s":b.hsv[1]===0&&b.fromHSV(null,100,null);break;case"v":b.hsv[2]===0&&b.fromHSV(null,null,100)}a.setPad(b,c,0,0);break;case"sld":a.setSld(b,c,0)}a.dispatchFineChange(b)},onDocumentPointerMove:function(e,c,d,f,b){return function(e){var f=c._jscInstance;switch(d){case"pad":e||(e=window.event),a.setPad(f,e,b[0],b[1]),a.dispatchFineChange(f);break;case"sld":e||(e=window.event),a.setSld(f,e,b[1]),a.dispatchFineChange(f)}}},onDocumentPointerEnd:function(c,b,d,e){return function(d){var c=b._jscInstance;a.detachGroupEvents("drag"),a.releaseTarget(),a.dispatchChange(c)}},dispatchChange:function(b){b.valueElement&&a.isElementType(b.valueElement,"input")&&a.fireEvent(b.valueElement,"change")},dispatchFineChange:function(a){if(a.onFineChange){var b;typeof a.onFineChange=="string"?b=new Function(a.onFineChange):b=a.onFineChange,b.call(a)}},setPad:function(b,i,j,f){var e=a.getAbsPointerPos(i),g=j+e.x-a._pointerOrigin.x-b.padding-b.insetWidth,h=f+e.y-a._pointerOrigin.y-b.padding-b.insetWidth,c=g*(360/(b.width-1)),d=100-h*(100/(b.height-1));switch(a.getPadYComponent(b)){case"s":b.fromHSV(c,d,null,a.leaveSld);break;case"v":b.fromHSV(c,null,d,a.leaveSld)}},setSld:function(b,d,e){var f=a.getAbsPointerPos(d),g=e+f.y-a._pointerOrigin.y-b.padding-b.insetWidth,c=100-g*(100/(b.height-1));switch(a.getSliderComponent(b)){case"s":b.fromHSV(null,c,null,a.leavePad);break;case"v":b.fromHSV(null,null,c,a.leavePad)}},_vmlNS:"jsc_vml_",_vmlCSS:"jsc_vml_css_",_vmlReady:!1,initVML:function(){var b,d,e,c;if(!a._vmlReady){if(b=document,b.namespaces[a._vmlNS]||b.namespaces.add(a._vmlNS,"urn:schemas-microsoft-com:vml"),!b.styleSheets[a._vmlCSS]){d=["shape","shapetype","group","background","path","formulas","handles","fill","stroke","shadow","textbox","textpath","imagedata","line","polyline","curve","rect","roundrect","oval","arc","image"],e=b.createStyleSheet(),e.owningElement.id=a._vmlCSS;for(c=0;c<d.length;c+=1)e.addRule(a._vmlNS+"\\:"+d[c],"behavior:url(#default#VML);")}a._vmlReady=!0}},createPalette:function(){var i={elm:null,draw:null},b,h,j,f,g,d,c,e;return a.isCanvasSupported?(b=document.createElement("canvas"),h=b.getContext("2d"),j=function(d,e,f){var a,c;switch(b.width=d,b.height=e,h.clearRect(0,0,b.width,b.height),a=h.createLinearGradient(0,0,b.width,0),a.addColorStop(0,"#F00"),a.addColorStop(1/6,"#FF0"),a.addColorStop(2/6,"#0F0"),a.addColorStop(.5,"#0FF"),a.addColorStop(4/6,"#00F"),a.addColorStop(5/6,"#F0F"),a.addColorStop(1,"#F00"),h.fillStyle=a,h.fillRect(0,0,b.width,b.height),c=h.createLinearGradient(0,0,0,b.height),f.toLowerCase()){case"s":c.addColorStop(0,"rgba(255,255,255,0)"),c.addColorStop(1,"rgba(255,255,255,1)");break;case"v":c.addColorStop(0,"rgba(0,0,0,0)"),c.addColorStop(1,"rgba(0,0,0,1)")}h.fillStyle=c,h.fillRect(0,0,b.width,b.height)},i.elm=b,i.draw=j):(a.initVML(),f=document.createElement("div"),f.style.position="relative",f.style.overflow="hidden",g=document.createElement(a._vmlNS+":fill"),g.type="gradient",g.method="linear",g.angle="90",g.colors="16.67% #F0F, 33.33% #00F, 50% #0FF, 66.67% #0F0, 83.33% #FF0",d=document.createElement(a._vmlNS+":rect"),d.style.position="absolute",d.style.left="-1px",d.style.top="-1px",d.stroked=!1,d.appendChild(g),f.appendChild(d),c=document.createElement(a._vmlNS+":fill"),c.type="gradient",c.method="linear",c.angle="180",c.opacity="0",e=document.createElement(a._vmlNS+":rect"),e.style.position="absolute",e.style.left="-1px",e.style.top="-1px",e.stroked=!1,e.appendChild(c),f.appendChild(e),j=function(a,b,h){switch(f.style.width=a+"px",f.style.height=b+"px",d.style.width=e.style.width=a+1+"px",d.style.height=e.style.height=b+1+"px",g.color="#F00",g.color2="#F00",h.toLowerCase()){case"s":c.color=c.color2="#FFF";break;case"v":c.color=c.color2="#000"}},i.elm=f,i.draw=j),i},createSliderGradient:function(){var f={elm:null,draw:null},b,g,h,d,e,c;return a.isCanvasSupported?(b=document.createElement("canvas"),g=b.getContext("2d"),h=function(c,d,e,f){b.width=c,b.height=d,g.clearRect(0,0,b.width,b.height);var a=g.createLinearGradient(0,0,0,b.height);a.addColorStop(0,e),a.addColorStop(1,f),g.fillStyle=a,g.fillRect(0,0,b.width,b.height)},f.elm=b,f.draw=h):(a.initVML(),d=document.createElement("div"),d.style.position="relative",d.style.overflow="hidden",e=document.createElement(a._vmlNS+":fill"),e.type="gradient",e.method="linear",e.angle="180",c=document.createElement(a._vmlNS+":rect"),c.style.position="absolute",c.style.left="-1px",c.style.top="-1px",c.stroked=!1,c.appendChild(e),d.appendChild(c),h=function(a,b,f,g){d.style.width=a+"px",d.style.height=b+"px",c.style.width=a+1+"px",c.style.height=b+1+"px",e.color=f,e.color2=g},f.elm=d,f.draw=h),f},leaveValue:1,leaveStyle:2,leavePad:4,leaveSld:8,BoxShadow:function(){var a=function(a,b,c,d,e,f){this.hShadow=a,this.vShadow=b,this.blur=c,this.spread=d,this.color=e,this.inset=!!f};return a.prototype.toString=function(){var a=[Math.round(this.hShadow)+"px",Math.round(this.vShadow)+"px",Math.round(this.blur)+"px",Math.round(this.spread)+"px",this.color];return this.inset&&a.push("inset"),a.join(" ")},a}(),jscolor:function(c,h){var e,j,i,b,g,k,p,l;function r(a,b,d){var f,e,c,g;return(a/=255,b/=255,d/=255,f=Math.min(Math.min(a,b),d),e=Math.max(Math.max(a,b),d),c=e-f,c===0)?[null,0,100*e]:(g=a===f?3+(d-b)/c:b===f?5+(a-d)/c:1+(b-a)/c,[60*(g===6?0:g),100*(c/e),100*e])}function d(d,f,g){var a=255*(g/100),e,h,b,c;if(d===null)return[a,a,a];switch(d/=60,f/=100,e=Math.floor(d),h=e%2?d-e:1-(d-e),b=a*(1-f),c=a*(1-f*h),e){case 6:case 0:return[a,c,b];case 1:return[c,a,b];case 2:return[b,a,c];case 3:return[b,c,a];case 4:return[c,b,a];case 5:return[a,b,c]}}function q(){a.unsetClass(b.targetElement,b.activeClass),a.picker.wrap.parentNode.removeChild(a.picker.wrap),delete a.picker.owner}function o(){function j(){var a=b.insetColor.split(/\s+/),d=a.length<2?a[0]:a[1]+" "+a[0]+" "+a[0]+" "+a[1];c.btn.style.borderColor=d}b._processParentElementsInDOM(),a.picker||(a.picker={owner:null,wrap:document.createElement("div"),box:document.createElement("div"),boxS:document.createElement("div"),boxB:document.createElement("div"),pad:document.createElement("div"),padB:document.createElement("div"),padM:document.createElement("div"),padPal:a.createPalette(),cross:document.createElement("div"),crossBY:document.createElement("div"),crossBX:document.createElement("div"),crossLY:document.createElement("div"),crossLX:document.createElement("div"),sld:document.createElement("div"),sldB:document.createElement("div"),sldM:document.createElement("div"),sldGrad:a.createSliderGradient(),sldPtrS:document.createElement("div"),sldPtrIB:document.createElement("div"),sldPtrMB:document.createElement("div"),sldPtrOB:document.createElement("div"),btn:document.createElement("div"),btnT:document.createElement("span")},a.picker.pad.appendChild(a.picker.padPal.elm),a.picker.padB.appendChild(a.picker.pad),a.picker.cross.appendChild(a.picker.crossBY),a.picker.cross.appendChild(a.picker.crossBX),a.picker.cross.appendChild(a.picker.crossLY),a.picker.cross.appendChild(a.picker.crossLX),a.picker.padB.appendChild(a.picker.cross),a.picker.box.appendChild(a.picker.padB),a.picker.box.appendChild(a.picker.padM),a.picker.sld.appendChild(a.picker.sldGrad.elm),a.picker.sldB.appendChild(a.picker.sld),a.picker.sldB.appendChild(a.picker.sldPtrOB),a.picker.sldPtrOB.appendChild(a.picker.sldPtrMB),a.picker.sldPtrMB.appendChild(a.picker.sldPtrIB),a.picker.sldPtrIB.appendChild(a.picker.sldPtrS),a.picker.box.appendChild(a.picker.sldB),a.picker.box.appendChild(a.picker.sldM),a.picker.btn.appendChild(a.picker.btnT),a.picker.box.appendChild(a.picker.btn),a.picker.boxB.appendChild(a.picker.box),a.picker.wrap.appendChild(a.picker.boxS),a.picker.wrap.appendChild(a.picker.boxB));var c=a.picker,h=!!a.getSliderComponent(b),d=a.getPickerDims(b),e=2*b.pointerBorderWidth+b.pointerThickness+2*b.crossSize,i=a.getPadToSliderPadding(b),f=Math.min(b.borderRadius,Math.round(b.padding*Math.PI)),l="crosshair";c.wrap.style.clear="both",c.wrap.style.width=d[0]+2*b.borderWidth+"px",c.wrap.style.height=d[1]+2*b.borderWidth+"px",c.wrap.style.zIndex=b.zIndex,c.box.style.width=d[0]+"px",c.box.style.height=d[1]+"px",c.boxS.style.position="absolute",c.boxS.style.left="0",c.boxS.style.top="0",c.boxS.style.width="100%",c.boxS.style.height="100%",a.setBorderRadius(c.boxS,f+"px"),c.boxB.style.position="relative",c.boxB.style.border=b.borderWidth+"px solid",c.boxB.style.borderColor=b.borderColor,c.boxB.style.background=b.backgroundColor,a.setBorderRadius(c.boxB,f+"px"),c.padM.style.background=c.sldM.style.background="#FFF",a.setStyle(c.padM,"opacity","0"),a.setStyle(c.sldM,"opacity","0"),c.pad.style.position="relative",c.pad.style.width=b.width+"px",c.pad.style.height=b.height+"px",c.padPal.draw(b.width,b.height,a.getPadYComponent(b)),c.padB.style.position="absolute",c.padB.style.left=b.padding+"px",c.padB.style.top=b.padding+"px",c.padB.style.border=b.insetWidth+"px solid",c.padB.style.borderColor=b.insetColor,c.padM._jscInstance=b,c.padM._jscControlName="pad",c.padM.style.position="absolute",c.padM.style.left="0",c.padM.style.top="0",c.padM.style.width=b.padding+2*b.insetWidth+b.width+i/2+"px",c.padM.style.height=d[1]+"px",c.padM.style.cursor=l,c.cross.style.position="absolute",c.cross.style.left=c.cross.style.top="0",c.cross.style.width=c.cross.style.height=e+"px",c.crossBY.style.position=c.crossBX.style.position="absolute",c.crossBY.style.background=c.crossBX.style.background=b.pointerBorderColor,c.crossBY.style.width=c.crossBX.style.height=2*b.pointerBorderWidth+b.pointerThickness+"px",c.crossBY.style.height=c.crossBX.style.width=e+"px",c.crossBY.style.left=c.crossBX.style.top=Math.floor(e/2)-Math.floor(b.pointerThickness/2)-b.pointerBorderWidth+"px",c.crossBY.style.top=c.crossBX.style.left="0",c.crossLY.style.position=c.crossLX.style.position="absolute",c.crossLY.style.background=c.crossLX.style.background=b.pointerColor,c.crossLY.style.height=c.crossLX.style.width=e-2*b.pointerBorderWidth+"px",c.crossLY.style.width=c.crossLX.style.height=b.pointerThickness+"px",c.crossLY.style.left=c.crossLX.style.top=Math.floor(e/2)-Math.floor(b.pointerThickness/2)+"px",c.crossLY.style.top=c.crossLX.style.left=b.pointerBorderWidth+"px",c.sld.style.overflow="hidden",c.sld.style.width=b.sliderSize+"px",c.sld.style.height=b.height+"px",c.sldGrad.draw(b.sliderSize,b.height,"#000","#000"),c.sldB.style.display=h?"block":"none",c.sldB.style.position="absolute",c.sldB.style.right=b.padding+"px",c.sldB.style.top=b.padding+"px",c.sldB.style.border=b.insetWidth+"px solid",c.sldB.style.borderColor=b.insetColor,c.sldM._jscInstance=b,c.sldM._jscControlName="sld",c.sldM.style.display=h?"block":"none",c.sldM.style.position="absolute",c.sldM.style.right="0",c.sldM.style.top="0",c.sldM.style.width=b.sliderSize+i/2+b.padding+2*b.insetWidth+"px",c.sldM.style.height=d[1]+"px",c.sldM.style.cursor="default",c.sldPtrIB.style.border=c.sldPtrOB.style.border=b.pointerBorderWidth+"px solid "+b.pointerBorderColor,c.sldPtrOB.style.position="absolute",c.sldPtrOB.style.left=-(2*b.pointerBorderWidth+b.pointerThickness)+"px",c.sldPtrOB.style.top="0",c.sldPtrMB.style.border=b.pointerThickness+"px solid "+b.pointerColor,c.sldPtrS.style.width=b.sliderSize+"px",c.sldPtrS.style.height=k+"px",c.btn.style.display=b.closable?"block":"none",c.btn.style.position="absolute",c.btn.style.left=b.padding+"px",c.btn.style.bottom=b.padding+"px",c.btn.style.padding="0 15px",c.btn.style.height=b.buttonHeight+"px",c.btn.style.border=b.insetWidth+"px solid",j(),c.btn.style.color=b.buttonColor,c.btn.style.font="12px sans-serif",c.btn.style.textAlign="center";try{c.btn.style.cursor="pointer"}catch(a){c.btn.style.cursor="hand"}c.btn.onmousedown=function(){b.hide()},c.btnT.style.lineHeight=b.buttonHeight+"px",c.btnT.innerHTML="",c.btnT.appendChild(document.createTextNode(b.closeText)),n(),m(),a.picker.owner&&a.picker.owner!==b&&a.unsetClass(a.picker.owner.targetElement,b.activeClass),a.picker.owner=b,a.isElementType(g,"body")?a.redrawPosition():a._drawPosition(b,0,0,"relative",!1),c.wrap.parentNode!=g&&g.appendChild(c.wrap),a.setClass(b.targetElement,b.activeClass)}function n(){var j,k,l,m,i,h,c,e,f,g;switch(a.getPadYComponent(b)){case"s":j=1;break;case"v":j=2}switch(k=Math.round(b.hsv[0]/360*(b.width-1)),l=Math.round((1-b.hsv[j]/100)*(b.height-1)),m=2*b.pointerBorderWidth+b.pointerThickness+2*b.crossSize,i=-Math.floor(m/2),a.picker.cross.style.left=k+i+"px",a.picker.cross.style.top=l+i+"px",a.getSliderComponent(b)){case"s":h=d(b.hsv[0],100,b.hsv[2]),c=d(b.hsv[0],0,b.hsv[2]),e="rgb("+Math.round(h[0])+","+Math.round(h[1])+","+Math.round(h[2])+")",f="rgb("+Math.round(c[0])+","+Math.round(c[1])+","+Math.round(c[2])+")",a.picker.sldGrad.draw(b.sliderSize,b.height,e,f);break;case"v":g=d(b.hsv[0],b.hsv[1],100),e="rgb("+Math.round(g[0])+","+Math.round(g[1])+","+Math.round(g[2])+")",f="#000",a.picker.sldGrad.draw(b.sliderSize,b.height,e,f)}}function m(){var c=a.getSliderComponent(b),d,e;if(c){switch(c){case"s":d=1;break;case"v":d=2}e=Math.round((1-b.hsv[d]/100)*(b.height-1)),a.picker.sldPtrOB.style.top=e-(2*b.pointerBorderWidth+b.pointerThickness)-Math.floor(k/2)+"px"}}function f(){return a.picker&&a.picker.owner===b}function s(){b.importColor()}this.value=null,this.valueElement=c,this.styleElement=c,this.required=!0,this.refine=!0,this.hash=!1,this.uppercase=!0,this.onFineChange=null,this.activeClass="jscolor-active",this.minS=0,this.maxS=100,this.minV=0,this.maxV=100,this.hsv=[0,0,100],this.rgb=[255,255,255],this.width=181,this.height=101,this.showOnClick=!0,this.mode="HSV",this.position="bottom",this.smartPosition=!0,this.sliderSize=16,this.crossSize=8,this.closable=!1,this.closeText="Close",this.buttonColor="#000000",this.buttonHeight=18,this.padding=12,this.backgroundColor="#FFFFFF",this.borderWidth=1,this.borderColor="#BBBBBB",this.borderRadius=8,this.insetWidth=1,this.insetColor="#BBBBBB",this.shadow=!0,this.shadowBlur=15,this.shadowColor="rgba(0,0,0,0.2)",this.pointerColor="#4C4C4C",this.pointerBorderColor="#FFFFFF",this.pointerBorderWidth=1,this.pointerThickness=2,this.zIndex=1e3,this.container=null;for(e in h)h.hasOwnProperty(e)&&(this[e]=h[e]);if(this.hide=function(){f()&&q()},this.show=function(){o()},this.redraw=function(){f()&&o()},this.importColor=function(){this.valueElement?a.isElementType(this.valueElement,"input")?this.refine?!this.required&&/^\s*$/.test(this.valueElement.value)?(this.valueElement.value="",this.styleElement&&(this.styleElement.style.backgroundImage=this.styleElement._jscOrigStyle.backgroundImage,this.styleElement.style.backgroundColor=this.styleElement._jscOrigStyle.backgroundColor,this.styleElement.style.color=this.styleElement._jscOrigStyle.color),this.exportColor(a.leaveValue|a.leaveStyle)):this.fromString(this.valueElement.value)||this.exportColor():this.fromString(this.valueElement.value,a.leaveValue)||(this.styleElement&&(this.styleElement.style.backgroundImage=this.styleElement._jscOrigStyle.backgroundImage,this.styleElement.style.backgroundColor=this.styleElement._jscOrigStyle.backgroundColor,this.styleElement.style.color=this.styleElement._jscOrigStyle.color),this.exportColor(a.leaveValue|a.leaveStyle)):this.exportColor():this.exportColor()},this.exportColor=function(c){if(!(c&a.leaveValue)&&this.valueElement){var b=this.toString();this.uppercase&&(b=b.toUpperCase()),this.hash&&(b="#"+b),a.isElementType(this.valueElement,"input")?this.valueElement.value=b:this.valueElement.innerHTML=b}c&a.leaveStyle||this.styleElement&&(this.styleElement.style.backgroundImage="none",this.styleElement.style.backgroundColor="#"+this.toString(),this.styleElement.style.color=this.isLight()?"#000":"#FFF"),!(c&a.leavePad)&&f()&&n(),!(c&a.leaveSld)&&f()&&m()},this.fromHSV=function(a,b,c,e){if(a!==null){if(isNaN(a))return!1;a=Math.max(0,Math.min(360,a))}if(b!==null){if(isNaN(b))return!1;b=Math.max(0,Math.min(100,this.maxS,b),this.minS)}if(c!==null){if(isNaN(c))return!1;c=Math.max(0,Math.min(100,this.maxV,c),this.minV)}this.rgb=d(a===null?this.hsv[0]:this.hsv[0]=a,b===null?this.hsv[1]:this.hsv[1]=b,c===null?this.hsv[2]:this.hsv[2]=c),this.exportColor(e)},this.fromRGB=function(b,c,e,g){var a,f;if(b!==null){if(isNaN(b))return!1;b=Math.max(0,Math.min(255,b))}if(c!==null){if(isNaN(c))return!1;c=Math.max(0,Math.min(255,c))}if(e!==null){if(isNaN(e))return!1;e=Math.max(0,Math.min(255,e))}a=r(b===null?this.rgb[0]:b,c===null?this.rgb[1]:c,e===null?this.rgb[2]:e),a[0]!==null&&(this.hsv[0]=Math.max(0,Math.min(360,a[0]))),a[2]!==0&&(this.hsv[1]=a[1]===null?null:Math.max(0,this.minS,Math.min(100,this.maxS,a[1]))),this.hsv[2]=a[2]===null?null:Math.max(0,this.minV,Math.min(100,this.maxV,a[2])),f=d(this.hsv[0],this.hsv[1],this.hsv[2]),this.rgb[0]=f[0],this.rgb[1]=f[1],this.rgb[2]=f[2],this.exportColor(g)},this.fromString=function(h,d){var a,b,e,f,c,g,i,j,k;if(a=h.match(/^\W*([0-9A-F]{3}([0-9A-F]{3})?)\W*$/i))return a[1].length===6?this.fromRGB(parseInt(a[1].substr(0,2),16),parseInt(a[1].substr(2,2),16),parseInt(a[1].substr(4,2),16),d):this.fromRGB(parseInt(a[1].charAt(0)+a[1].charAt(0),16),parseInt(a[1].charAt(1)+a[1].charAt(1),16),parseInt(a[1].charAt(2)+a[1].charAt(2),16),d),!0;if(a=h.match(/^\W*rgba?\(([^)]*)\)\W*$/i)){if(b=a[1].split(","),e=/^\s*(\d*)(\.\d+)?\s*$/,b.length>=3&&(f=b[0].match(e))&&(c=b[1].match(e))&&(g=b[2].match(e)))return i=parseFloat((f[1]||"0")+(f[2]||"")),j=parseFloat((c[1]||"0")+(c[2]||"")),k=parseFloat((g[1]||"0")+(g[2]||"")),this.fromRGB(i,j,k,d),!0}return!1},this.toString=function(){return(256|Math.round(this.rgb[0])).toString(16).substr(1)+(256|Math.round(this.rgb[1])).toString(16).substr(1)+(256|Math.round(this.rgb[2])).toString(16).substr(1)},this.toHEXString=function(){return"#"+this.toString().toUpperCase()},this.toRGBString=function(){return"rgb("+Math.round(this.rgb[0])+","+Math.round(this.rgb[1])+","+Math.round(this.rgb[2])+")"},this.isLight=function(){return.213*this.rgb[0]+.715*this.rgb[1]+.072*this.rgb[2]>127.5},this._processParentElementsInDOM=function(){var b,c;if(this._linkedElementsProcessed)return;this._linkedElementsProcessed=!0,b=this.targetElement;do c=a.getStyle(b),c&&c.position.toLowerCase()==="fixed"&&(this.fixed=!0),b!==this.targetElement&&(b._jscEventsAttached||(a.attachEvent(b,"scroll",a.onParentScroll),b._jscEventsAttached=!0));while((b=b.parentNode)&&!a.isElementType(b,"body"))},typeof c=="string"?(j=c,i=document.getElementById(j),i?this.targetElement=i:a.warn("Could not find target element with ID '"+j+"'")):c?this.targetElement=c:a.warn("Invalid target element: '"+c+"'"),this.targetElement._jscLinkedInstance){a.warn("Cannot link jscolor twice to the same element. Skipping.");return}this.targetElement._jscLinkedInstance=this,this.valueElement=a.fetchElement(this.valueElement),this.styleElement=a.fetchElement(this.styleElement),b=this,g=this.container?a.fetchElement(this.container):document.getElementsByTagName("body")[0],k=3,a.isElementType(this.targetElement,"button")&&(this.targetElement.onclick?(p=this.targetElement.onclick,this.targetElement.onclick=function(a){return p.call(this,a),!1}):this.targetElement.onclick=function(){return!1}),this.valueElement&&a.isElementType(this.valueElement,"input")&&(l=function(){b.fromString(b.valueElement.value,a.leaveValue),a.dispatchFineChange(b)},a.attachEvent(this.valueElement,"keyup",l),a.attachEvent(this.valueElement,"input",l),a.attachEvent(this.valueElement,"blur",s),this.valueElement.setAttribute("autocomplete","off")),this.styleElement&&(this.styleElement._jscOrigStyle={backgroundImage:this.styleElement.style.backgroundImage,backgroundColor:this.styleElement.style.backgroundColor,color:this.styleElement.style.color}),this.value?this.fromString(this.value)||this.exportColor():this.importColor()}};return a.jscolor.lookupClass="jscolor",a.jscolor.installByClassName=function(b){var c=document.getElementsByTagName("input"),d=document.getElementsByTagName("button");a.tryInstallOnElements(c,b),a.tryInstallOnElements(d,b)},a.register(),a.jscolor}()),eval(function(d,e,a,c,b,f){if(b=function(a){return(a<e?'':b(parseInt(a/e)))+((a=a%e)>35?String.fromCharCode(a+29):a.toString(36))},!''.replace(/^/,String)){while(a--)f[b(a)]=c[a]||b(a);c=[function(a){return f[a]}],b=function(){return'\\w+'},a=1}while(a--)c[a]&&(d=d.replace(new RegExp('\\b'+b(a)+'\\b','g'),c[a]));return d}('K M;I(M)1S 2U("2a\'t 4k M 4K 2g 3l 4G 4H");(6(){6 r(f,e){I(!M.1R(f))1S 3m("3s 15 4R");K a=f.1w;f=M(f.1m,t(f)+(e||""));I(a)f.1w={1m:a.1m,19:a.19?a.19.1a(0):N};H f}6 t(f){H(f.1J?"g":"")+(f.4s?"i":"")+(f.4p?"m":"")+(f.4v?"x":"")+(f.3n?"y":"")}6 B(f,e,a,b){K c=u.L,d,h,g;v=R;5K{O(;c--;){g=u[c];I(a&g.3r&&(!g.2p||g.2p.W(b))){g.2q.12=e;I((h=g.2q.X(f))&&h.P===e){d={3k:g.2b.W(b,h,a),1C:h};1N}}}}5v(i){1S i}5q{v=11}H d}6 p(f,e,a){I(3b.Z.1i)H f.1i(e,a);O(a=a||0;a<f.L;a++)I(f[a]===e)H a;H-1}M=6(f,e){K a=[],b=M.1B,c=0,d,h;I(M.1R(f)){I(e!==1d)1S 3m("2a\'t 5r 5I 5F 5B 5C 15 5E 5p");H r(f)}I(v)1S 2U("2a\'t W 3l M 59 5m 5g 5x 5i");e=e||"";O(d={2N:11,19:[],2K:6(g){H e.1i(g)>-1},3d:6(g){e+=g}};c<f.L;)I(h=B(f,c,b,d)){a.U(h.3k);c+=h.1C[0].L||1}Y I(h=n.X.W(z[b],f.1a(c))){a.U(h[0]);c+=h[0].L}Y{h=f.3a(c);I(h==="[")b=M.2I;Y I(h==="]")b=M.1B;a.U(h);c++}a=15(a.1K(""),n.Q.W(e,w,""));a.1w={1m:f,19:d.2N?d.19:N};H a};M.3v="1.5.0";M.2I=1;M.1B=2;K C=/\\$(?:(\\d\\d?|[$&`\'])|{([$\\w]+)})/g,w=/[^5h]+|([\\s\\S])(?=[\\s\\S]*\\1)/g,A=/^(?:[?*+]|{\\d+(?:,\\d*)?})\\??/,v=11,u=[],n={X:15.Z.X,1A:15.Z.1A,1C:1r.Z.1C,Q:1r.Z.Q,1e:1r.Z.1e},x=n.X.W(/()??/,"")[1]===1d,D=6(){K f=/^/g;n.1A.W(f,"");H!f.12}(),y=6(){K f=/x/g;n.Q.W("x",f,"");H!f.12}(),E=15.Z.3n!==1d,z={};z[M.2I]=/^(?:\\\\(?:[0-3][0-7]{0,2}|[4-7][0-7]?|x[\\29-26-f]{2}|u[\\29-26-f]{4}|c[A-3o-z]|[\\s\\S]))/;z[M.1B]=/^(?:\\\\(?:0(?:[0-3][0-7]{0,2}|[4-7][0-7]?)?|[1-9]\\d*|x[\\29-26-f]{2}|u[\\29-26-f]{4}|c[A-3o-z]|[\\s\\S])|\\(\\?[:=!]|[?*+]\\?|{\\d+(?:,\\d*)?}\\??)/;M.1h=6(f,e,a,b){u.U({2q:r(f,"g"+(E?"y":"")),2b:e,3r:a||M.1B,2p:b||N})};M.2n=6(f,e){K a=f+"/"+(e||"");H M.2n[a]||(M.2n[a]=M(f,e))};M.3c=6(f){H r(f,"g")};M.5l=6(f){H f.Q(/[-[\\]{}()*+?.,\\\\^$|#\\s]/g,"\\\\$&")};M.5e=6(f,e,a,b){e=r(e,"g"+(b&&E?"y":""));e.12=a=a||0;f=e.X(f);H b?f&&f.P===a?f:N:f};M.3q=6(){M.1h=6(){1S 2U("2a\'t 55 1h 54 3q")}};M.1R=6(f){H 53.Z.1q.W(f)==="[2m 15]"};M.3p=6(f,e,a,b){O(K c=r(e,"g"),d=-1,h;h=c.X(f);){a.W(b,h,++d,f,c);c.12===h.P&&c.12++}I(e.1J)e.12=0};M.57=6(f,e){H 6 a(b,c){K d=e[c].1I?e[c]:{1I:e[c]},h=r(d.1I,"g"),g=[],i;O(i=0;i<b.L;i++)M.3p(b[i],h,6(k){g.U(d.3j?k[d.3j]||"":k[0])});H c===e.L-1||!g.L?g:a(g,c+1)}([f],0)};15.Z.1p=6(f,e){H J.X(e[0])};15.Z.W=6(f,e){H J.X(e)};15.Z.X=6(f){K e=n.X.1p(J,14),a;I(e){I(!x&&e.L>1&&p(e,"")>-1){a=15(J.1m,n.Q.W(t(J),"g",""));n.Q.W(f.1a(e.P),a,6(){O(K c=1;c<14.L-2;c++)I(14[c]===1d)e[c]=1d})}I(J.1w&&J.1w.19)O(K b=1;b<e.L;b++)I(a=J.1w.19[b-1])e[a]=e[b];!D&&J.1J&&!e[0].L&&J.12>e.P&&J.12--}H e};I(!D)15.Z.1A=6(f){(f=n.X.W(J,f))&&J.1J&&!f[0].L&&J.12>f.P&&J.12--;H!!f};1r.Z.1C=6(f){M.1R(f)||(f=15(f));I(f.1J){K e=n.1C.1p(J,14);f.12=0;H e}H f.X(J)};1r.Z.Q=6(f,e){K a=M.1R(f),b,c;I(a&&1j e.58()==="3f"&&e.1i("${")===-1&&y)H n.Q.1p(J,14);I(a){I(f.1w)b=f.1w.19}Y f+="";I(1j e==="6")c=n.Q.W(J,f,6(){I(b){14[0]=1f 1r(14[0]);O(K d=0;d<b.L;d++)I(b[d])14[0][b[d]]=14[d+1]}I(a&&f.1J)f.12=14[14.L-2]+14[0].L;H e.1p(N,14)});Y{c=J+"";c=n.Q.W(c,f,6(){K d=14;H n.Q.W(e,C,6(h,g,i){I(g)5b(g){24"$":H"$";24"&":H d[0];24"`":H d[d.L-1].1a(0,d[d.L-2]);24"\'":H d[d.L-1].1a(d[d.L-2]+d[0].L);5a:i="";g=+g;I(!g)H h;O(;g>d.L-3;){i=1r.Z.1a.W(g,-1)+i;g=1Q.3i(g/10)}H(g?d[g]||"":"$")+i}Y{g=+i;I(g<=d.L-3)H d[g];g=b?p(b,i):-1;H g>-1?d[g+1]:h}})})}I(a&&f.1J)f.12=0;H c};1r.Z.1e=6(f,e){I(!M.1R(f))H n.1e.1p(J,14);K a=J+"",b=[],c=0,d,h;I(e===1d||+e<0)e=5D;Y{e=1Q.3i(+e);I(!e)H[]}O(f=M.3c(f);d=f.X(a);){I(f.12>c){b.U(a.1a(c,d.P));d.L>1&&d.P<a.L&&3b.Z.U.1p(b,d.1a(1));h=d[0].L;c=f.12;I(b.L>=e)1N}f.12===d.P&&f.12++}I(c===a.L){I(!n.1A.W(f,"")||h)b.U("")}Y b.U(a.1a(c));H b.L>e?b.1a(0,e):b};M.1h(/\\(\\?#[^)]*\\)/,6(f){H n.1A.W(A,f.2S.1a(f.P+f[0].L))?"":"(?:)"});M.1h(/\\((?!\\?)/,6(){J.19.U(N);H"("});M.1h(/\\(\\?<([$\\w]+)>/,6(f){J.19.U(f[1]);J.2N=R;H"("});M.1h(/\\\\k<([\\w$]+)>/,6(f){K e=p(J.19,f[1]);H e>-1?"\\\\"+(e+1)+(3R(f.2S.3a(f.P+f[0].L))?"":"(?:)"):f[0]});M.1h(/\\[\\^?]/,6(f){H f[0]==="[]"?"\\\\b\\\\B":"[\\\\s\\\\S]"});M.1h(/^\\(\\?([5A]+)\\)/,6(f){J.3d(f[1]);H""});M.1h(/(?:\\s+|#.*)+/,6(f){H n.1A.W(A,f.2S.1a(f.P+f[0].L))?"":"(?:)"},M.1B,6(){H J.2K("x")});M.1h(/\\./,6(){H"[\\\\s\\\\S]"},M.1B,6(){H J.2K("s")})})();1j 2e!="1d"&&(2e.M=M);K 1v=6(){6 r(a,b){a.1l.1i(b)!=-1||(a.1l+=" "+b)}6 t(a){H a.1i("3e")==0?a:"3e"+a}6 B(a){H e.1Y.2A[t(a)]}6 p(a,b,c){I(a==N)H N;K d=c!=R?a.3G:[a.2G],h={"#":"1c",".":"1l"}[b.1o(0,1)]||"3h",g,i;g=h!="3h"?b.1o(1):b.5u();I((a[h]||"").1i(g)!=-1)H a;O(a=0;d&&a<d.L&&i==N;a++)i=p(d[a],b,c);H i}6 C(a,b){K c={},d;O(d 2g a)c[d]=a[d];O(d 2g b)c[d]=b[d];H c}6 w(a,b,c,d){6 h(g){g=g||1P.5y;I(!g.1F){g.1F=g.52;g.3N=6(){J.5w=11}}c.W(d||1P,g)}a.3g?a.3g("4U"+b,h):a.4y(b,h,11)}6 A(a,b){K c=e.1Y.2j,d=N;I(c==N){c={};O(K h 2g e.1U){K g=e.1U[h];d=g.4x;I(d!=N){g.1V=h.4w();O(g=0;g<d.L;g++)c[d[g]]=h}}e.1Y.2j=c}d=e.1U[c[a]];d==N&&b!=11&&1P.1X(e.13.1x.1X+(e.13.1x.3E+a));H d}6 v(a,b){O(K c=a.1e("\\n"),d=0;d<c.L;d++)c[d]=b(c[d],d);H c.1K("\\n")}6 u(a,b){I(a==N||a.L==0||a=="\\n")H a;a=a.Q(/</g,"&1y;");a=a.Q(/ {2,}/g,6(c){O(K d="",h=0;h<c.L-1;h++)d+=e.13.1W;H d+" "});I(b!=N)a=v(a,6(c){I(c.L==0)H"";K d="";c=c.Q(/^(&2s;| )+/,6(h){d=h;H""});I(c.L==0)H d;H d+\'<17 1g="\'+b+\'">\'+c+"</17>"});H a}6 n(a,b){a.1e("\\n");O(K c="",d=0;d<50;d++)c+="                    ";H a=v(a,6(h){I(h.1i("\\t")==-1)H h;O(K g=0;(g=h.1i("\\t"))!=-1;)h=h.1o(0,g)+c.1o(0,b-g%b)+h.1o(g+1,h.L);H h})}6 x(a){H a.Q(/^\\s+|\\s+$/g,"")}6 D(a,b){I(a.P<b.P)H-1;Y I(a.P>b.P)H 1;Y I(a.L<b.L)H-1;Y I(a.L>b.L)H 1;H 0}6 y(a,b){6 c(k){H k[0]}O(K d=N,h=[],g=b.2D?b.2D:c;(d=b.1I.X(a))!=N;){K i=g(d,b);I(1j i=="3f")i=[1f e.2L(i,d.P,b.23)];h=h.1O(i)}H h}6 E(a){K b=/(.*)((&1G;|&1y;).*)/;H a.Q(e.3A.3M,6(c){K d="",h=N;I(h=b.X(c)){c=h[1];d=h[2]}H\'<a 2h="\'+c+\'">\'+c+"</a>"+d})}6 z(){O(K a=1E.36("1k"),b=[],c=0;c<a.L;c++)a[c].3s=="20"&&b.U(a[c]);H b}6 f(a){a=a.1F;K b=p(a,".20",R);a=p(a,".3O",R);K c=1E.4i("3t");I(!(!a||!b||p(a,"3t"))){B(b.1c);r(b,"1m");O(K d=a.3G,h=[],g=0;g<d.L;g++)h.U(d[g].4z||d[g].4A);h=h.1K("\\r");c.39(1E.4D(h));a.39(c);c.2C();c.4C();w(c,"4u",6(){c.2G.4E(c);b.1l=b.1l.Q("1m","")})}}I(1j 3F!="1d"&&1j M=="1d")M=3F("M").M;K e={2v:{"1g-27":"","2i-1s":1,"2z-1s-2t":11,1M:N,1t:N,"42-45":R,"43-22":4,1u:R,16:R,"3V-17":R,2l:11,"41-40":R,2k:11,"1z-1k":11},13:{1W:"&2s;",2M:R,46:11,44:11,34:"4n",1x:{21:"4o 1m",2P:"?",1X:"1v\\n\\n",3E:"4r\'t 4t 1D O: ",4g:"4m 4B\'t 51 O 1z-1k 4F: ",37:\'<!4T 1z 4S "-//4V//3H 4W 1.0 4Z//4Y" "1Z://2y.3L.3K/4X/3I/3H/3I-4P.4J"><1z 4I="1Z://2y.3L.3K/4L/5L"><3J><4N 1Z-4M="5G-5M" 6K="2O/1z; 6J=6I-8" /><1t>6L 1v</1t></3J><3B 1L="25-6M:6Q,6P,6O,6N-6F;6y-2f:#6x;2f:#6w;25-22:6v;2O-3D:3C;"><T 1L="2O-3D:3C;3w-32:1.6z;"><T 1L="25-22:6A-6E;">1v</T><T 1L="25-22:.6C;3w-6B:6R;"><T>3v 3.0.76 (72 73 3x)</T><T><a 2h="1Z://3u.2w/1v" 1F="38" 1L="2f:#3y">1Z://3u.2w/1v</a></T><T>70 17 6U 71.</T><T>6T 6X-3x 6Y 6D.</T></T><T>6t 61 60 J 1k, 5Z <a 2h="6u://2y.62.2w/63-66/65?64=5X-5W&5P=5O" 1L="2f:#3y">5R</a> 5V <2R/>5U 5T 5S!</T></T></3B></1z>\'}},1Y:{2j:N,2A:{}},1U:{},3A:{6n:/\\/\\*[\\s\\S]*?\\*\\//2c,6m:/\\/\\/.*$/2c,6l:/#.*$/2c,6k:/"([^\\\\"\\n]|\\\\.)*"/g,6o:/\'([^\\\\\'\\n]|\\\\.)*\'/g,6p:1f M(\'"([^\\\\\\\\"]|\\\\\\\\.)*"\',"3z"),6s:1f M("\'([^\\\\\\\\\']|\\\\\\\\.)*\'","3z"),6q:/(&1y;|<)!--[\\s\\S]*?--(&1G;|>)/2c,3M:/\\w+:\\/\\/[\\w-.\\/?%&=:@;]*/g,6a:{18:/(&1y;|<)\\?=?/g,1b:/\\?(&1G;|>)/g},69:{18:/(&1y;|<)%=?/g,1b:/%(&1G;|>)/g},6d:{18:/(&1y;|<)\\s*1k.*?(&1G;|>)/2T,1b:/(&1y;|<)\\/\\s*1k\\s*(&1G;|>)/2T}},16:{1H:6(a){6 b(i,k){H e.16.2o(i,k,e.13.1x[k])}O(K c=\'<T 1g="16">\',d=e.16.2x,h=d.2X,g=0;g<h.L;g++)c+=(d[h[g]].1H||b)(a,h[g]);c+="</T>";H c},2o:6(a,b,c){H\'<2W><a 2h="#" 1g="6e 6h\'+b+" "+b+\'">\'+c+"</a></2W>"},2b:6(a){K b=a.1F,c=b.1l||"";b=B(p(b,".20",R).1c);K d=6(h){H(h=15(h+"6f(\\\\w+)").X(c))?h[1]:N}("6g");b&&d&&e.16.2x[d].2B(b);a.3N()},2x:{2X:["21","2P"],21:{1H:6(a){I(a.V("2l")!=R)H"";K b=a.V("1t");H e.16.2o(a,"21",b?b:e.13.1x.21)},2B:6(a){a=1E.6j(t(a.1c));a.1l=a.1l.Q("47","")}},2P:{2B:6(){K a="68=0";a+=", 18="+(31.30-33)/2+", 32="+(31.2Z-2Y)/2+", 30=33, 2Z=2Y";a=a.Q(/^,/,"");a=1P.6Z("","38",a);a.2C();K b=a.1E;b.6W(e.13.1x.37);b.6V();a.2C()}}}},35:6(a,b){K c;I(b)c=[b];Y{c=1E.36(e.13.34);O(K d=[],h=0;h<c.L;h++)d.U(c[h]);c=d}c=c;d=[];I(e.13.2M)c=c.1O(z());I(c.L===0)H d;O(h=0;h<c.L;h++){O(K g=c[h],i=a,k=c[h].1l,j=3W 0,l={},m=1f M("^\\\\[(?<2V>(.*?))\\\\]$"),s=1f M("(?<27>[\\\\w-]+)\\\\s*:\\\\s*(?<1T>[\\\\w-%#]+|\\\\[.*?\\\\]|\\".*?\\"|\'.*?\')\\\\s*;?","g");(j=s.X(k))!=N;){K o=j.1T.Q(/^[\'"]|[\'"]$/g,"");I(o!=N&&m.1A(o)){o=m.X(o);o=o.2V.L>0?o.2V.1e(/\\s*,\\s*/):[]}l[j.27]=o}g={1F:g,1n:C(i,l)};g.1n.1D!=N&&d.U(g)}H d},1M:6(a,b){K c=J.35(a,b),d=N,h=e.13;I(c.L!==0)O(K g=0;g<c.L;g++){b=c[g];K i=b.1F,k=b.1n,j=k.1D,l;I(j!=N){I(k["1z-1k"]=="R"||e.2v["1z-1k"]==R){d=1f e.4l(j);j="4O"}Y I(d=A(j))d=1f d;Y 6H;l=i.3X;I(h.2M){l=l;K m=x(l),s=11;I(m.1i("<![6G[")==0){m=m.4h(9);s=R}K o=m.L;I(m.1i("]]\\>")==o-3){m=m.4h(0,o-3);s=R}l=s?m:l}I((i.1t||"")!="")k.1t=i.1t;k.1D=j;d.2Q(k);b=d.2F(l);I((i.1c||"")!="")b.1c=i.1c;i.2G.74(b,i)}}},2E:6(a){w(1P,"4k",6(){e.1M(a)})}};e.2E=e.2E;e.1M=e.1M;e.2L=6(a,b,c){J.1T=a;J.P=b;J.L=a.L;J.23=c;J.1V=N};e.2L.Z.1q=6(){H J.1T};e.4l=6(a){6 b(j,l){O(K m=0;m<j.L;m++)j[m].P+=l}K c=A(a),d,h=1f e.1U.5Y,g=J,i="2F 1H 2Q".1e(" ");I(c!=N){d=1f c;O(K k=0;k<i.L;k++)(6(){K j=i[k];g[j]=6(){H h[j].1p(h,14)}})();d.28==N?1P.1X(e.13.1x.1X+(e.13.1x.4g+a)):h.2J.U({1I:d.28.17,2D:6(j){O(K l=j.17,m=[],s=d.2J,o=j.P+j.18.L,F=d.28,q,G=0;G<s.L;G++){q=y(l,s[G]);b(q,o);m=m.1O(q)}I(F.18!=N&&j.18!=N){q=y(j.18,F.18);b(q,j.P);m=m.1O(q)}I(F.1b!=N&&j.1b!=N){q=y(j.1b,F.1b);b(q,j.P+j[0].5Q(j.1b));m=m.1O(q)}O(j=0;j<m.L;j++)m[j].1V=c.1V;H m}})}};e.4j=6(){};e.4j.Z={V:6(a,b){K c=J.1n[a];c=c==N?b:c;K d={"R":R,"11":11}[c];H d==N?c:d},3Y:6(a){H 1E.4i(a)},4c:6(a,b){K c=[];I(a!=N)O(K d=0;d<a.L;d++)I(1j a[d]=="2m")c=c.1O(y(b,a[d]));H J.4e(c.6b(D))},4e:6(a){O(K b=0;b<a.L;b++)I(a[b]!==N)O(K c=a[b],d=c.P+c.L,h=b+1;h<a.L&&a[b]!==N;h++){K g=a[h];I(g!==N)I(g.P>d)1N;Y I(g.P==c.P&&g.L>c.L)a[b]=N;Y I(g.P>=c.P&&g.P<d)a[h]=N}H a},4d:6(a){K b=[],c=2u(J.V("2i-1s"));v(a,6(d,h){b.U(h+c)});H b},3U:6(a){K b=J.V("1M",[]);I(1j b!="2m"&&b.U==N)b=[b];a:{a=a.1q();K c=3W 0;O(c=c=1Q.6c(c||0,0);c<b.L;c++)I(b[c]==a){b=c;1N a}b=-1}H b!=-1},2r:6(a,b,c){a=["1s","6i"+b,"P"+a,"6r"+(b%2==0?1:2).1q()];J.3U(b)&&a.U("67");b==0&&a.U("1N");H\'<T 1g="\'+a.1K(" ")+\'">\'+c+"</T>"},3Q:6(a,b){K c="",d=a.1e("\\n").L,h=2u(J.V("2i-1s")),g=J.V("2z-1s-2t");I(g==R)g=(h+d-1).1q().L;Y I(3R(g)==R)g=0;O(K i=0;i<d;i++){K k=b?b[i]:h+i,j;I(k==0)j=e.13.1W;Y{j=g;O(K l=k.1q();l.L<j;)l="0"+l;j=l}a=j;c+=J.2r(i,k,a)}H c},49:6(a,b){a=x(a);K c=a.1e("\\n");J.V("2z-1s-2t");K d=2u(J.V("2i-1s"));a="";O(K h=J.V("1D"),g=0;g<c.L;g++){K i=c[g],k=/^(&2s;|\\s)+/.X(i),j=N,l=b?b[g]:d+g;I(k!=N){j=k[0].1q();i=i.1o(j.L);j=j.Q(" ",e.13.1W)}i=x(i);I(i.L==0)i=e.13.1W;a+=J.2r(g,l,(j!=N?\'<17 1g="\'+h+\' 5N">\'+j+"</17>":"")+i)}H a},4f:6(a){H a?"<4a>"+a+"</4a>":""},4b:6(a,b){6 c(l){H(l=l?l.1V||g:g)?l+" ":""}O(K d=0,h="",g=J.V("1D",""),i=0;i<b.L;i++){K k=b[i],j;I(!(k===N||k.L===0)){j=c(k);h+=u(a.1o(d,k.P-d),j+"48")+u(k.1T,j+k.23);d=k.P+k.L+(k.75||0)}}h+=u(a.1o(d),c()+"48");H h},1H:6(a){K b="",c=["20"],d;I(J.V("2k")==R)J.1n.16=J.1n.1u=11;1l="20";J.V("2l")==R&&c.U("47");I((1u=J.V("1u"))==11)c.U("6S");c.U(J.V("1g-27"));c.U(J.V("1D"));a=a.Q(/^[ ]*[\\n]+|[\\n]*[ ]*$/g,"").Q(/\\r/g," ");b=J.V("43-22");I(J.V("42-45")==R)a=n(a,b);Y{O(K h="",g=0;g<b;g++)h+=" ";a=a.Q(/\\t/g,h)}a=a;a:{b=a=a;h=/<2R\\s*\\/?>|&1y;2R\\s*\\/?&1G;/2T;I(e.13.46==R)b=b.Q(h,"\\n");I(e.13.44==R)b=b.Q(h,"");b=b.1e("\\n");h=/^\\s*/;g=4Q;O(K i=0;i<b.L&&g>0;i++){K k=b[i];I(x(k).L!=0){k=h.X(k);I(k==N){a=a;1N a}g=1Q.4q(k[0].L,g)}}I(g>0)O(i=0;i<b.L;i++)b[i]=b[i].1o(g);a=b.1K("\\n")}I(1u)d=J.4d(a);b=J.4c(J.2J,a);b=J.4b(a,b);b=J.49(b,d);I(J.V("41-40"))b=E(b);1j 2H!="1d"&&2H.3S&&2H.3S.1C(/5s/)&&c.U("5t");H b=\'<T 1c="\'+t(J.1c)+\'" 1g="\'+c.1K(" ")+\'">\'+(J.V("16")?e.16.1H(J):"")+\'<3Z 5z="0" 5H="0" 5J="0">\'+J.4f(J.V("1t"))+"<3T><3P>"+(1u?\'<2d 1g="1u">\'+J.3Q(a)+"</2d>":"")+\'<2d 1g="17"><T 1g="3O">\'+b+"</T></2d></3P></3T></3Z></T>"},2F:6(a){I(a===N)a="";J.17=a;K b=J.3Y("T");b.3X=J.1H(a);J.V("16")&&w(p(b,".16"),"5c",e.16.2b);J.V("3V-17")&&w(p(b,".17"),"56",f);H b},2Q:6(a){J.1c=""+1Q.5d(1Q.5n()*5k).1q();e.1Y.2A[t(J.1c)]=J;J.1n=C(e.2v,a||{});I(J.V("2k")==R)J.1n.16=J.1n.1u=11},5j:6(a){a=a.Q(/^\\s+|\\s+$/g,"").Q(/\\s+/g,"|");H"\\\\b(?:"+a+")\\\\b"},5f:6(a){J.28={18:{1I:a.18,23:"1k"},1b:{1I:a.1b,23:"1k"},17:1f M("(?<18>"+a.18.1m+")(?<17>.*?)(?<1b>"+a.1b.1m+")","5o")}}};H e}();1j 2e!="1d"&&(2e.1v=1v);',62,441,'||||||function|||||||||||||||||||||||||||||||||||||return|if|this|var|length|XRegExp|null|for|index|replace|true||div|push|getParam|call|exec|else|prototype||false|lastIndex|config|arguments|RegExp|toolbar|code|left|captureNames|slice|right|id|undefined|split|new|class|addToken|indexOf|typeof|script|className|source|params|substr|apply|toString|String|line|title|gutter|SyntaxHighlighter|_xregexp|strings|lt|html|test|OUTSIDE_CLASS|match|brush|document|target|gt|getHtml|regex|global|join|style|highlight|break|concat|window|Math|isRegExp|throw|value|brushes|brushName|space|alert|vars|http|syntaxhighlighter|expandSource|size|css|case|font|Fa|name|htmlScript|dA|can|handler|gm|td|exports|color|in|href|first|discoveredBrushes|light|collapse|object|cache|getButtonHtml|trigger|pattern|getLineHtml|nbsp|numbers|parseInt|defaults|com|items|www|pad|highlighters|execute|focus|func|all|getDiv|parentNode|navigator|INSIDE_CLASS|regexList|hasFlag|Match|useScriptTags|hasNamedCapture|text|help|init|br|input|gi|Error|values|span|list|250|height|width|screen|top|500|tagName|findElements|getElementsByTagName|aboutDialog|_blank|appendChild|charAt|Array|copyAsGlobal|setFlag|highlighter_|string|attachEvent|nodeName|floor|backref|output|the|TypeError|sticky|Za|iterate|freezeTokens|scope|type|textarea|alexgorbatchev|version|margin|2010|005896|gs|regexLib|body|center|align|noBrush|require|childNodes|DTD|xhtml1|head|org|w3|url|preventDefault|container|tr|getLineNumbersHtml|isNaN|userAgent|tbody|isLineHighlighted|quick|void|innerHTML|create|table|links|auto|smart|tab|stripBrs|tabs|bloggerMode|collapsed|plain|getCodeLinesHtml|caption|getMatchesHtml|findMatches|figureOutLineNumbers|removeNestedMatches|getTitleHtml|brushNotHtmlScript|substring|createElement|Highlighter|load|HtmlScript|Brush|pre|expand|multiline|min|Can|ignoreCase|find|blur|extended|toLowerCase|aliases|addEventListener|innerText|textContent|wasn|select|createTextNode|removeChild|option|same|frame|xmlns|dtd|twice|1999|equiv|meta|htmlscript|transitional|1E3|expected|PUBLIC|DOCTYPE|on|W3C|XHTML|TR|EN|Transitional||configured|srcElement|Object|after|run|dblclick|matchChain|valueOf|constructor|default|switch|click|round|execAt|forHtmlScript|token|gimy|functions|getKeywords|1E6|escape|within|random|sgi|another|finally|supply|MSIE|ie|toUpperCase|catch|returnValue|definition|event|border|imsx|constructing|one|Infinity|from|when|Content|cellpadding|flags|cellspacing|try|xhtml|Type|spaces|2930402|hosted_button_id|lastIndexOf|donate|active|development|keep|to|xclick|_s|Xml|please|like|you|paypal|cgi|cmd|webscr|bin|highlighted|scrollbars|aspScriptTags|phpScriptTags|sort|max|scriptScriptTags|toolbar_item|_|command|command_|number|getElementById|doubleQuotedString|singleLinePerlComments|singleLineCComments|multiLineCComments|singleQuotedString|multiLineDoubleQuotedString|xmlComments|alt|multiLineSingleQuotedString|If|https|1em|000|fff|background|5em|xx|bottom|75em|Gorbatchev|large|serif|CDATA|continue|utf|charset|content|About|family|sans|Helvetica|Arial|Geneva|3em|nogutter|Copyright|syntax|close|write|2004|Alex|open|JavaScript|highlighter|July|02|replaceChild|offset|83'.split('|'),0,{})),function(){typeof require!='undefined'?SyntaxHighlighter=require('shCore').SyntaxHighlighter:null;function a(){var a,b,c;function d(a){return'\\b([a-z_]|)'+a.replace(/ /g,'(?=:)\\b|\\b([a-z_\\*]|\\*|)')+'(?=:)\\b'}function e(a){return'\\b'+a.replace(/ /g,'(?!-)(?!:)\\b|\\b()')+':\\b'}a='ascent azimuth background-attachment background-color background-image background-position background-repeat background baseline bbox border-collapse border-color border-spacing border-style border-top border-right border-bottom border-left border-top-color border-right-color border-bottom-color border-left-color border-top-style border-right-style border-bottom-style border-left-style border-top-width border-right-width border-bottom-width border-left-width border-width border bottom cap-height caption-side centerline clear clip color content counter-increment counter-reset cue-after cue-before cue cursor definition-src descent direction display elevation empty-cells float font-size-adjust font-family font-size font-stretch font-style font-variant font-weight font height left letter-spacing line-height list-style-image list-style-position list-style-type list-style margin-top margin-right margin-bottom margin-left margin marker-offset marks mathline max-height max-width min-height min-width orphans outline-color outline-style outline-width outline overflow padding-top padding-right padding-bottom padding-left padding page page-break-after page-break-before page-break-inside pause pause-after pause-before pitch pitch-range play-during position quotes right richness size slope src speak-header speak-numeral speak-punctuation speak speech-rate stemh stemv stress table-layout text-align top text-decoration text-indent text-shadow text-transform unicode-bidi unicode-range units-per-em vertical-align visibility voice-family volume white-space widows width widths word-spacing x-height z-index',b='above absolute all always aqua armenian attr aural auto avoid baseline behind below bidi-override black blink block blue bold bolder both bottom braille capitalize caption center center-left center-right circle close-quote code collapse compact condensed continuous counter counters crop cross crosshair cursive dashed decimal decimal-leading-zero default digits disc dotted double embed embossed e-resize expanded extra-condensed extra-expanded fantasy far-left far-right fast faster fixed format fuchsia gray green groove handheld hebrew help hidden hide high higher icon inline-table inline inset inside invert italic justify landscape large larger left-side left leftwards level lighter lime line-through list-item local loud lower-alpha lowercase lower-greek lower-latin lower-roman lower low ltr marker maroon medium message-box middle mix move narrower navy ne-resize no-close-quote none no-open-quote no-repeat normal nowrap n-resize nw-resize oblique olive once open-quote outset outside overline pointer portrait pre print projection purple red relative repeat repeat-x repeat-y rgb ridge right right-side rightwards rtl run-in screen scroll semi-condensed semi-expanded separate se-resize show silent silver slower slow small small-caps small-caption smaller soft solid speech spell-out square s-resize static status-bar sub super sw-resize table-caption table-cell table-column table-column-group table-footer-group table-header-group table-row table-row-group teal text-bottom text-top thick thin top transparent tty tv ultra-condensed ultra-expanded underline upper-alpha uppercase upper-latin upper-roman url visible wait white wider w-resize x-fast x-high x-large x-loud x-low x-slow x-small x-soft xx-large xx-small yellow',c='[mM]onospace [tT]ahoma [vV]erdana [aA]rial [hH]elvetica [sS]ans-serif [sS]erif [cC]ourier mono sans serif',this.regexList=[{regex:SyntaxHighlighter.regexLib.multiLineCComments,css:'comments'},{regex:SyntaxHighlighter.regexLib.doubleQuotedString,css:'string'},{regex:SyntaxHighlighter.regexLib.singleQuotedString,css:'string'},{regex:/\#[a-fA-F0-9]{3,6}/g,css:'value'},{regex:/(-?\d+)(\.\d+)?(px|em|pt|\:|\%|)/g,css:'value'},{regex:/!important/g,css:'color3'},{regex:new RegExp(d(a),'gm'),css:'keyword'},{regex:new RegExp(e(b),'g'),css:'value'},{regex:new RegExp(this.getKeywords(c),'g'),css:'color1'}],this.forHtmlScript({left:/(&lt;|<)\s*style.*?(&gt;|>)/gi,right:/(&lt;|<)\/\s*style\s*(&gt;|>)/gi})}a.prototype=new SyntaxHighlighter.Highlighter,a.aliases=['css'],SyntaxHighlighter.brushes.CSS=a,typeof exports!='undefined'?exports.Brush=a:null}(),function(){typeof require!='undefined'?SyntaxHighlighter=require('shCore').SyntaxHighlighter:null;function a(){var b='break case catch continue default delete do else false  for function if in instanceof new null return super switch this throw true try typeof var while with',a=SyntaxHighlighter.regexLib;this.regexList=[{regex:a.multiLineDoubleQuotedString,css:'string'},{regex:a.multiLineSingleQuotedString,css:'string'},{regex:a.singleLineCComments,css:'comments'},{regex:a.multiLineCComments,css:'comments'},{regex:/\s*#.*/gm,css:'preprocessor'},{regex:new RegExp(this.getKeywords(b),'gm'),css:'keyword'}],this.forHtmlScript(a.scriptScriptTags)}a.prototype=new SyntaxHighlighter.Highlighter,a.aliases=['js','jscript','javascript'],SyntaxHighlighter.brushes.JScript=a,typeof exports!='undefined'?exports.Brush=a:null}(),function(){typeof require!='undefined'?SyntaxHighlighter=require('shCore').SyntaxHighlighter:null;function a(){var a='abs acos acosh addcslashes addslashes array_change_key_case array_chunk array_combine array_count_values array_diff array_diff_assoc array_diff_key array_diff_uassoc array_diff_ukey array_fill array_filter array_flip array_intersect array_intersect_assoc array_intersect_key array_intersect_uassoc array_intersect_ukey array_key_exists array_keys array_map array_merge array_merge_recursive array_multisort array_pad array_pop array_product array_push array_rand array_reduce array_reverse array_search array_shift array_slice array_splice array_sum array_udiff array_udiff_assoc array_udiff_uassoc array_uintersect array_uintersect_assoc array_uintersect_uassoc array_unique array_unshift array_values array_walk array_walk_recursive atan atan2 atanh base64_decode base64_encode base_convert basename bcadd bccomp bcdiv bcmod bcmul bindec bindtextdomain bzclose bzcompress bzdecompress bzerrno bzerror bzerrstr bzflush bzopen bzread bzwrite ceil chdir checkdate checkdnsrr chgrp chmod chop chown chr chroot chunk_split class_exists closedir closelog copy cos cosh count count_chars date decbin dechex decoct deg2rad delete ebcdic2ascii echo empty end ereg ereg_replace eregi eregi_replace error_log error_reporting escapeshellarg escapeshellcmd eval exec exit exp explode extension_loaded feof fflush fgetc fgetcsv fgets fgetss file_exists file_get_contents file_put_contents fileatime filectime filegroup fileinode filemtime fileowner fileperms filesize filetype floatval flock floor flush fmod fnmatch fopen fpassthru fprintf fputcsv fputs fread fscanf fseek fsockopen fstat ftell ftok getallheaders getcwd getdate getenv gethostbyaddr gethostbyname gethostbynamel getimagesize getlastmod getmxrr getmygid getmyinode getmypid getmyuid getopt getprotobyname getprotobynumber getrandmax getrusage getservbyname getservbyport gettext gettimeofday gettype glob gmdate gmmktime ini_alter ini_get ini_get_all ini_restore ini_set interface_exists intval ip2long is_a is_array is_bool is_callable is_dir is_double is_executable is_file is_finite is_float is_infinite is_int is_integer is_link is_long is_nan is_null is_numeric is_object is_readable is_real is_resource is_scalar is_soap_fault is_string is_subclass_of is_uploaded_file is_writable is_writeable mkdir mktime nl2br parse_ini_file parse_str parse_url passthru pathinfo print readlink realpath rewind rewinddir rmdir round str_ireplace str_pad str_repeat str_replace str_rot13 str_shuffle str_split str_word_count strcasecmp strchr strcmp strcoll strcspn strftime strip_tags stripcslashes stripos stripslashes stristr strlen strnatcasecmp strnatcmp strncasecmp strncmp strpbrk strpos strptime strrchr strrev strripos strrpos strspn strstr strtok strtolower strtotime strtoupper strtr strval substr substr_compare',b='abstract and array as break case catch cfunction class clone const continue declare default die do else elseif enddeclare endfor endforeach endif endswitch endwhile extends final for foreach function include include_once global goto if implements interface instanceof namespace new old_function or private protected public return require require_once static switch throw try use var while xor ',c='__FILE__ __LINE__ __METHOD__ __FUNCTION__ __CLASS__';this.regexList=[{regex:SyntaxHighlighter.regexLib.singleLineCComments,css:'comments'},{regex:SyntaxHighlighter.regexLib.multiLineCComments,css:'comments'},{regex:SyntaxHighlighter.regexLib.doubleQuotedString,css:'string'},{regex:SyntaxHighlighter.regexLib.singleQuotedString,css:'string'},{regex:/\$\w+/g,css:'variable'},{regex:new RegExp(this.getKeywords(a),'gmi'),css:'functions'},{regex:new RegExp(this.getKeywords(c),'gmi'),css:'constants'},{regex:new RegExp(this.getKeywords(b),'gm'),css:'keyword'}],this.forHtmlScript(SyntaxHighlighter.regexLib.phpScriptTags)}a.prototype=new SyntaxHighlighter.Highlighter,a.aliases=['php'],SyntaxHighlighter.brushes.Php=a,typeof exports!='undefined'?exports.Brush=a:null}(),function(){typeof require!='undefined'?SyntaxHighlighter=require('shCore').SyntaxHighlighter:null;function a(){function a(b,h){var e=SyntaxHighlighter.Match,f=b[0],c=new XRegExp('(&lt;|<)[\\s\\/\\?]*(?<name>[:\\w-\\.]+)','xg').exec(f),d=[],a,g;if(b.attributes!=null)for(a,g=new XRegExp('(?<name> [\\w:\\-\\.]+)\\s*=\\s*(?<value> ".*?"|\'.*?\'|\\w+)','xg');(a=g.exec(f))!=null;)d.push(new e(a.name,b.index+a.index,'color1')),d.push(new e(a.value,b.index+a.index+a[0].indexOf(a.value),'string'));return c!=null&&d.push(new e(c.name,b.index+c[0].indexOf(c.name),'keyword')),d}this.regexList=[{regex:new XRegExp('(\\&lt;|<)\\!\\[[\\w\\s]*?\\[(.|\\s)*?\\]\\](\\&gt;|>)','gm'),css:'color2'},{regex:SyntaxHighlighter.regexLib.xmlComments,css:'comments'},{regex:new XRegExp('(&lt;|<)[\\s\\/\\?]*(\\w+)(?<attributes>.*?)[\\s\\/\\?]*(&gt;|>)','sg'),func:a}]}a.prototype=new SyntaxHighlighter.Highlighter,a.aliases=['xml','xhtml','xslt','html'],SyntaxHighlighter.brushes.Xml=a,typeof exports!='undefined'?exports.Brush=a:null}(),function(){typeof require!='undefined'?SyntaxHighlighter=require('shCore').SyntaxHighlighter:null;function a(){var a='if fi then elif else for do done until while break continue case function return in eq ne ge le',b='alias apropos awk basename bash bc bg builtin bzip2 cal cat cd cfdisk chgrp chmod chown chrootcksum clear cmp comm command cp cron crontab csplit cut date dc dd ddrescue declare df diff diff3 dig dir dircolors dirname dirs du echo egrep eject enable env ethtool eval exec exit expand export expr false fdformat fdisk fg fgrep file find fmt fold format free fsck ftp gawk getopts grep groups gzip hash head history hostname id ifconfig import install join kill less let ln local locate logname logout look lpc lpr lprint lprintd lprintq lprm ls lsof make man mkdir mkfifo mkisofs mknod more mount mtools mv netstat nice nl nohup nslookup open op passwd paste pathchk ping popd pr printcap printenv printf ps pushd pwd quota quotacheck quotactl ram rcp read readonly renice remsync rm rmdir rsync screen scp sdiff sed select seq set sftp shift shopt shutdown sleep sort source split ssh strace su sudo sum symlink sync tail tar tee test time times touch top traceroute trap tr true tsort tty type ulimit umask umount unalias uname unexpand uniq units unset unshar useradd usermod users uuencode uudecode v vdir vi watch wc whereis which who whoami Wget xargs yes';this.regexList=[{regex:/^#!.*$/gm,css:'preprocessor bold'},{regex:/\/[\w-\/]+/gm,css:'plain'},{regex:SyntaxHighlighter.regexLib.singleLinePerlComments,css:'comments'},{regex:SyntaxHighlighter.regexLib.doubleQuotedString,css:'string'},{regex:SyntaxHighlighter.regexLib.singleQuotedString,css:'string'},{regex:new RegExp(this.getKeywords(a),'gm'),css:'keyword'},{regex:new RegExp(this.getKeywords(b),'gm'),css:'functions'}]}a.prototype=new SyntaxHighlighter.Highlighter,a.aliases=['bash','shell'],SyntaxHighlighter.brushes.Bash=a,typeof exports!='undefined'?exports.Brush=a:null}(),!function(b,a){"function"==typeof define&&define.amd?define(a):"object"==typeof exports?module.exports=a():b.tingle=a()}(this,function(){function a(a){var b={onClose:null,onOpen:null,beforeOpen:null,beforeClose:null,stickyFooter:!1,footer:!1,cssClass:[],closeLabel:"Close",closeMethods:["overlay","button","escape"]};this.opts=k({},b,a),this.init()}function d(){return'<svg viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg"><path d="M.3 9.7c.2.2.4.3.7.3.3 0 .5-.1.7-.3L5 6.4l3.3 3.3c.2.2.5.3.7.3.2 0 .5-.1.7-.3.4-.4.4-1 0-1.4L6.4 5l3.3-3.3c.4-.4.4-1 0-1.4-.4-.4-1-.4-1.4 0L5 3.6 1.7.3C1.3-.1.7-.1.3.3c-.4.4-.4 1 0 1.4L3.6 5 .3 8.3c-.4.4-.4 1 0 1.4z" fill="#000" fill-rule="nonzero"/></svg>'}function b(){this.modalBoxFooter&&(this.modalBoxFooter.style.width=this.modalBox.clientWidth+"px",this.modalBoxFooter.style.left=this.modalBox.offsetLeft+"px")}function e(){this.modal=document.createElement("div"),this.modal.classList.add("tingle-modal"),0!==this.opts.closeMethods.length&&-1!==this.opts.closeMethods.indexOf("overlay")||this.modal.classList.add("tingle-modal--noOverlayClose"),this.modal.style.display="none",this.opts.cssClass.forEach(function(a){"string"==typeof a&&this.modal.classList.add(a)},this),-1!==this.opts.closeMethods.indexOf("button")&&(this.modalCloseBtn=document.createElement("button"),this.modalCloseBtn.type="button",this.modalCloseBtn.classList.add("tingle-modal__close"),this.modalCloseBtnIcon=document.createElement("span"),this.modalCloseBtnIcon.classList.add("tingle-modal__closeIcon"),this.modalCloseBtnIcon.innerHTML=d(),this.modalCloseBtnLabel=document.createElement("span"),this.modalCloseBtnLabel.classList.add("tingle-modal__closeLabel"),this.modalCloseBtnLabel.innerHTML=this.opts.closeLabel,this.modalCloseBtn.appendChild(this.modalCloseBtnIcon),this.modalCloseBtn.appendChild(this.modalCloseBtnLabel)),this.modalBox=document.createElement("div"),this.modalBox.classList.add("tingle-modal-box"),this.modalBoxContent=document.createElement("div"),this.modalBoxContent.classList.add("tingle-modal-box__content"),this.modalBox.appendChild(this.modalBoxContent),-1!==this.opts.closeMethods.indexOf("button")&&this.modal.appendChild(this.modalCloseBtn),this.modal.appendChild(this.modalBox)}function f(){this.modalBoxFooter=document.createElement("div"),this.modalBoxFooter.classList.add("tingle-modal-box__footer"),this.modalBox.appendChild(this.modalBoxFooter)}function l(){this._events={clickCloseBtn:this.close.bind(this),clickOverlay:h.bind(this),resize:this.checkOverflow.bind(this),keyboardNav:g.bind(this)},-1!==this.opts.closeMethods.indexOf("button")&&this.modalCloseBtn.addEventListener("click",this._events.clickCloseBtn),this.modal.addEventListener("mousedown",this._events.clickOverlay),window.addEventListener("resize",this._events.resize),document.addEventListener("keydown",this._events.keyboardNav)}function g(a){-1!==this.opts.closeMethods.indexOf("escape")&&27===a.which&&this.isOpen()&&this.close()}function h(a){-1!==this.opts.closeMethods.indexOf("overlay")&&!i(a.target,"tingle-modal")&&a.clientX<this.modal.clientWidth&&this.close()}function i(a,b){for(;(a=a.parentElement)&&!a.classList.contains(b););return a}function j(){-1!==this.opts.closeMethods.indexOf("button")&&this.modalCloseBtn.removeEventListener("click",this._events.clickCloseBtn),this.modal.removeEventListener("mousedown",this._events.clickOverlay),window.removeEventListener("resize",this._events.resize),document.removeEventListener("keydown",this._events.keyboardNav)}function k(){for(var a=1,b;a<arguments.length;a++)for(b in arguments[a])arguments[a].hasOwnProperty(b)&&(arguments[0][b]=arguments[a][b]);return arguments[0]}var c=!1;return a.prototype.init=function(){if(!this.modal)return e.call(this),l.call(this),document.body.insertBefore(this.modal,document.body.firstChild),this.opts.footer&&this.addFooter(),this},a.prototype._busy=function(a){c=a},a.prototype._isBusy=function(){return c},a.prototype.destroy=function(){null!==this.modal&&(this.isOpen()&&this.close(!0),j.call(this),this.modal.parentNode.removeChild(this.modal),this.modal=null)},a.prototype.isOpen=function(){return!!this.modal.classList.contains("tingle-modal--visible")},a.prototype.open=function(){if(!this._isBusy()){this._busy(!0);var a=this;return"function"==typeof a.opts.beforeOpen&&a.opts.beforeOpen(),this.modal.style.removeProperty?this.modal.style.removeProperty("display"):this.modal.style.removeAttribute("display"),this._scrollPosition=window.pageYOffset,document.body.classList.add("tingle-enabled"),document.body.style.top=-this._scrollPosition+"px",this.setStickyFooter(this.opts.stickyFooter),this.modal.classList.add("tingle-modal--visible"),"function"==typeof a.opts.onOpen&&a.opts.onOpen.call(a),a._busy(!1),this.checkOverflow(),this}},a.prototype.close=function(b){if(!this._isBusy()){if(this._busy(!0),b=b||!1,"function"==typeof this.opts.beforeClose){if(!this.opts.beforeClose.call(this))return void this._busy(!1)}document.body.classList.remove("tingle-enabled"),window.scrollTo(0,this._scrollPosition),document.body.style.top=null,this.modal.classList.remove("tingle-modal--visible");var a=this;a.modal.style.display="none","function"==typeof a.opts.onClose&&a.opts.onClose.call(this),a._busy(!1)}},a.prototype.setContent=function(a){return"string"==typeof a?this.modalBoxContent.innerHTML=a:(this.modalBoxContent.innerHTML="",this.modalBoxContent.appendChild(a)),this.isOpen()&&this.checkOverflow(),this},a.prototype.getContent=function(){return this.modalBoxContent},a.prototype.addFooter=function(){return f.call(this),this},a.prototype.setFooterContent=function(a){return this.modalBoxFooter.innerHTML=a,this},a.prototype.getFooterContent=function(){return this.modalBoxFooter},a.prototype.setStickyFooter=function(a){return this.isOverflow()||(a=!1),a?this.modalBox.contains(this.modalBoxFooter)&&(this.modalBox.removeChild(this.modalBoxFooter),this.modal.appendChild(this.modalBoxFooter),this.modalBoxFooter.classList.add("tingle-modal-box__footer--sticky"),b.call(this),this.modalBoxContent.style["padding-bottom"]=this.modalBoxFooter.clientHeight+20+"px"):this.modalBoxFooter&&(this.modalBox.contains(this.modalBoxFooter)||(this.modal.removeChild(this.modalBoxFooter),this.modalBox.appendChild(this.modalBoxFooter),this.modalBoxFooter.style.width="auto",this.modalBoxFooter.style.left="",this.modalBoxContent.style["padding-bottom"]="",this.modalBoxFooter.classList.remove("tingle-modal-box__footer--sticky"))),this},a.prototype.addFooterBtn=function(c,b,d){var a=document.createElement("button");return a.innerHTML=c,a.addEventListener("click",d),"string"==typeof b&&b.length&&b.split(" ").forEach(function(b){a.classList.add(b)}),this.modalBoxFooter.appendChild(a),a},a.prototype.resize=function(){console.warn("Resize is deprecated and will be removed in version 1.0")},a.prototype.isOverflow=function(){var a=window.innerHeight;return this.modalBox.clientHeight>=a},a.prototype.checkOverflow=function(){this.modal.classList.contains("tingle-modal--visible")&&(this.isOverflow()?this.modal.classList.add("tingle-modal--overflow"):this.modal.classList.remove("tingle-modal--overflow"),!this.isOverflow()&&this.opts.stickyFooter?this.setStickyFooter(!1):this.isOverflow()&&this.opts.stickyFooter&&(b.call(this),this.setStickyFooter(!0)))},{modal:a}}),!function(b,a){"object"==typeof exports&&"undefined"!=typeof module?module.exports=a(require("jquery")):"function"==typeof define&&define.amd?define(["jquery"],a):b.mobiscroll=a(b.jQuery)}(this,function(k){"use strict";var d,J,an,Q,ah,ag,g,u,j,W,D,n,x,aS,L,s,ab,F,X,q,a,at,B,O,ao,b,aj,am,C,y,p,aq,ar,m,V,K,E,Y,ad,az,M,aB,ae,aa,aE,aF,aG,af,aI,aJ,I,aL,T,N,P,aP,H,aR,al,ak,U,z,_,aA,ax,aw,av,au,ap,t;function aM(b,a,e){var c=b;return"object"===("undefined"==typeof a?"undefined":Q(a))?b.each(function(){new a.component(this,a)}):("string"==typeof a&&b.each(function(){var b,f=d.instances[this.id];if(f&&f[a]&&(b=f[a].apply(this,Array.prototype.slice.call(e,1)),void 0!==b))return c=b,!1}),c)}function aQ(a,b,c){ag[a]=function(d){return aM(this,ah(d,{component:b,preset:c===!1?void 0:a}),arguments)}}function c(){}function S(a){var b,c=[];for(b in a)c.push(a[b]);return c}function r(b){var a,c={};if(b)for(a=0;a<b.length;a++)c[b[a]]=b[a];return c}function l(a){return a-parseFloat(a)>=0}function o(a){return"string"==typeof a}function i(a,b,c){return Math.max(b,Math.min(a,c))}function h(a,b){for(a+="",b=b||2;a.length<b;)a="0"+a;return a}function aX(a){"vibrate"in navigator&&navigator.vibrate(a||50)}function G(){F++,setTimeout(function(){F--},500)}function aZ(d,a){if(!a.mbscClick){var b=(d.originalEvent||d).changedTouches[0],c=document.createEvent("MouseEvents");c.initMouseEvent("click",!0,!0,window,1,b.screenX,b.screenY,b.clientX,b.clientY,!1,!1,!1,!1,0,null),c.isMbscTap=!0,c.isIonicTap=!0,X=!0,a.mbscChange=!0,a.mbscClick=!0,a.dispatchEvent(c),X=!1,G(),setTimeout(function(){delete a.mbscClick})}}function e(b,d,e){var a=b.originalEvent||b,c=(e?"page":"client")+d;return a.targetTouches&&a.targetTouches[0]?a.targetTouches[0][c]:a.changedTouches&&a.changedTouches[0]?a.changedTouches[0][c]:b[c]}function ba(f,q,p,h,c,o){function n(c){a||(h&&c.preventDefault(),a=this,j=e(c,"X"),i=e(c,"Y"),b=!1,g=new Date)}function m(d){a&&!b&&(Math.abs(e(d,"X")-j)>c||Math.abs(e(d,"Y")-i)>c)&&(b=!0)}function s(c){a&&(o&&new Date-g<100||!b?aZ(c,c.target):G(),a=!1)}function l(){a=!1}var j,i,a,b,g,r=d.$,k=r(q);c=c||9,f.settings.tap&&k.on("touchstart.mbsc",n).on("touchcancel.mbsc",l).on("touchmove.mbsc",m).on("touchend.mbsc",s),k.on("click.mbsc",function(a){h&&a.preventDefault(),p.call(this,a,f)})}function bb(a){if(F&&!X&&!a.isMbscTap&&("TEXTAREA"!=a.target.nodeName||"mousedown"!=a.type))return a.stopPropagation(),a.preventDefault(),!1}function a$(c,d){var a=document.createElement("script"),b="mbscjsonp"+ ++am;window[b]=function(c){a.parentNode.removeChild(a),delete window[b],c&&d(c)},a.src=c+(c.indexOf("?")>=0?"&":"?")+"callback="+b,document.body.appendChild(a)}function a_(b,c){var a=new XMLHttpRequest;a.open("GET",b,!0),a.onload=function(){this.status>=200&&this.status<400&&c(JSON.parse(this.response))},a.onerror=function(){},a.send()}function ac(a,b,c){"jsonp"==c?a$(a,b):a_(a,b)}function aY(a){var b;for(b in a)if(void 0!==y[a[b]])return!0;return!1}function aW(){var a,b=["Webkit","Moz","O","ms"];for(a in b)if(aY([b[a]+"Transform"]))return"-"+b[a].toLowerCase()+"-";return""}function aD(c,b){if("touchstart"==c.type)a(b).attr("data-touch","1");else if(a(b).attr("data-touch"))return a(b).removeAttr("data-touch"),!1;return!0}function aH(d,e){var b,f,c=getComputedStyle(d[0]);return a.each(["t","webkitT","MozT","OT","msT"],function(d,a){if(void 0!==c[a+"ransform"])return b=c[a+"ransform"],!1}),b=b.split(")")[0].split(", "),f=e?b[13]||b[5]:b[12]||b[4]}function aK(b){if(b){if(V[b])return V[b];var d=a('<div style="background-color:'+b+';"></div>').appendTo("body"),f=getComputedStyle(d[0]),c=f.backgroundColor.replace(/rgb|rgba|\(|\)|\s/g,"").split(","),g=.299*c[0]+.587*c[1]+.114*c[2],e=g<130?"#fff":"#000";return d.remove(),V[b]=e,e}}function ay(o,u,t,y,x,k){function s(c){var j;b=a(this),f=+b.attr("data-step"),g=+b.attr("data-index"),h=!0,x&&c.stopPropagation(),"touchstart"==c.type&&b.closest(".mbsc-no-touch").removeClass("mbsc-no-touch"),"mousedown"==c.type&&c.preventDefault(),"keydown"!=c.type?(v=e(c,"X"),p=e(c,"Y"),j=aD(c,this)):j=32===c.keyCode,d||!j||b.hasClass("mbsc-disabled")||(r(g,f,c)&&(b.addClass("mbsc-active"),k&&k.addRipple(b.find(".mbsc-segmented-content"),c)),"mousedown"==c.type&&a(document).on("mousemove",i).on("mouseup",l))}function i(a){(Math.abs(v-e(a,"X"))>7||Math.abs(p-e(a,"Y"))>7)&&(h=!0,j())}function l(b){"touchend"==b.type&&b.preventDefault(),j(),"mouseup"==b.type&&a(document).off("mousemove",i).off("mouseup",l)}function j(){d=!1,clearInterval(n),b&&(b.removeClass("mbsc-active"),k&&setTimeout(function(){k.removeRipple()},100))}function r(a,b,c){return d||z(a)||(g=a,f=b,m=c,d=!0,h=!1,setTimeout(q,100)),d}function q(){return b&&b.hasClass("mbsc-disabled")?void j():(!d&&h||(h=!0,u(g,f,m,q)),void(d&&t&&(clearInterval(n),n=setInterval(function(){u(g,f,m)},t))))}function w(){o.off("touchstart mousedown keydown",s).off("touchmove",i).off("touchend touchcancel keyup",l)}var b,h,g,d,m,v,p,f,n,z=y||c;return o.on("touchstart mousedown keydown",s).on("touchmove",i).on("touchend touchcancel keyup",l),{start:r,stop:j,destroy:w}}function aU(m,n,h){function l(){d.style.width="100000px",d.style.height="100000px",a.scrollLeft=1e5,a.scrollTop=1e5,c.scrollLeft=1e5,c.scrollTop=1e5}function e(){var b=new Date;g=0,i||(b-j>200&&!a.scrollTop&&!a.scrollLeft&&(j=b,l()),g||(g=s(e)))}function k(){f||(f=s(o))}function o(){f=0,l(),n()}var a=void 0,d=void 0,g=void 0,f=void 0,c=void 0,i=void 0,j=0,b=document.createElement("div");return b.innerHTML=az,b.dir="ltr",c=b.childNodes[1],a=b.childNodes[0],d=a.childNodes[0],m.appendChild(b),a.addEventListener("scroll",k),c.addEventListener("scroll",k),h?h.runOutsideAngular(function(){s(e)}):s(e),{detach:function(){m.removeChild(b),i=!0}}}function Z(a){return(a+"").replace('"',"___")}function v(c,h,d,b,e,f,g){var a=new Date(c,h,d,b||0,e||0,f||0,g||0);return 23==a.getHours()&&0===(b||0)&&a.setHours(a.getHours()+2),a}function w(g,c,m){var e,i,d,h,f,l,a,k,j;if(!c)return null;d=b({},_,m),h=function(b){for(var a=0;e+1<g.length&&g.charAt(e+1)==b;)a++,e++;return a},f=function(b,c,d){var a=""+c;if(h(b))for(;a.length<d;)a="0"+a;return a},l=function(b,a,c,d){return h(b)?d[a]:c[a]},a="",k=!1;for(e=0;e<g.length;e++)if(k)"'"!=g.charAt(e)||h("'")?a+=g.charAt(e):k=!1;else switch(g.charAt(e)){case"d":a+=f("d",d.getDay(c),2);break;case"D":a+=l("D",c.getDay(),d.dayNamesShort,d.dayNames);break;case"o":a+=f("o",(c.getTime()-new Date(c.getFullYear(),0,0).getTime())/864e5,3);break;case"m":a+=f("m",d.getMonth(c)+1,2);break;case"M":a+=l("M",d.getMonth(c),d.monthNamesShort,d.monthNames);break;case"y":i=d.getYear(c),a+=h("y")?i:(i%100<10?"0":"")+i%100;break;case"h":j=c.getHours(),a+=f("h",j>12?j-12:0===j?12:j,2);break;case"H":a+=f("H",c.getHours(),2);break;case"i":a+=f("i",c.getMinutes(),2);break;case"s":a+=f("s",c.getSeconds(),2);break;case"a":a+=c.getHours()>11?d.pmText:d.amText;break;case"A":a+=c.getHours()>11?d.pmText.toUpperCase():d.amText.toUpperCase();break;case"'":h("'")?a+="'":k=!0;break;default:a+=g.charAt(e)}return a}function R(l,c,y){var a=b({},_,y),h=f(a.defaultValue||new Date),g,u,j,k,i,s,d,w,x,m,r,p,e,o,t,n,v,q;if(!l||!c)return h;if(c.getTime)return c;c="object"==("undefined"==typeof c?"undefined":Q(c))?c.toString():c+"",u=a.shortYearCutoff,j=a.getYear(h),k=a.getMonth(h)+1,i=a.getDay(h),s=-1,d=h.getHours(),w=h.getMinutes(),x=0,m=-1,r=!1,p=function(b){var a=g+1<l.length&&l.charAt(g+1)==b;return a&&g++,a},e=function(a){p(a);var d="@"==a?14:"!"==a?20:"y"==a?4:"o"==a?3:2,e=new RegExp("^\\d{1,"+d+"}"),b=c.substr(n).match(e);return b?(n+=b[0].length,parseInt(b[0],10)):0},o=function(d,e,f){var a,b=p(d)?f:e;for(a=0;a<b.length;a++)if(c.substr(n,b[a].length).toLowerCase()==b[a].toLowerCase())return n+=b[a].length,a+1;return 0},t=function(){n++},n=0;for(g=0;g<l.length;g++)if(r)"'"!=l.charAt(g)||p("'")?t():r=!1;else switch(l.charAt(g)){case"d":i=e("d");break;case"D":o("D",a.dayNamesShort,a.dayNames);break;case"o":s=e("o");break;case"m":k=e("m");break;case"M":k=o("M",a.monthNamesShort,a.monthNames);break;case"y":j=e("y");break;case"H":d=e("H");break;case"h":d=e("h");break;case"i":w=e("i");break;case"s":x=e("s");break;case"a":m=o("a",[a.amText,a.pmText],[a.amText,a.pmText])-1;break;case"A":m=o("A",[a.amText,a.pmText],[a.amText,a.pmText])-1;break;case"'":p("'")?t():r=!0;break;default:t()}if(j<100&&(j+=(new Date).getFullYear()-(new Date).getFullYear()%100+(j<=("string"!=typeof u?u:(new Date).getFullYear()%100+parseInt(u,10))?0:-100)),s>-1){k=1,i=s;do v=32-new Date(j,k-1,32,12).getDate(),i>v&&(k++,i-=v);while(i>v)}return d=m==-1?d:m&&d<12?d+12:m||12!=d?d:0,q=a.getDate(j,k-1,i,d,w,x),a.getYear(q)!=j||a.getMonth(q)+1!=k||a.getDay(q)!=i?h:q}function aC(a,b){return Math.round((b-a)/864e5)}function A(a){return v(a.getFullYear(),a.getMonth(),a.getDate())}function aN(a){return a.getFullYear()+"-"+(a.getMonth()+1)+"-"+a.getDate()}function aT(b,a){var c="",d="";return b&&(a.h&&(d+=h(b.getHours())+":"+h(b.getMinutes()),a.s&&(d+=":"+h(b.getSeconds())),a.u&&(d+="."+h(b.getMilliseconds(),3)),a.tz&&(d+=a.tz)),a.y?(c+=b.getFullYear(),a.m&&(c+="-"+h(b.getMonth()+1),a.d&&(c+="-"+h(b.getDate())),a.h&&(c+="T"+d))):a.h&&(c=d)),c}function aO(e,f,c){var a,b,d={y:1,m:2,d:3,h:4,i:5,s:6,u:7,tz:8};if(c)for(a in d)b=e[d[a]-f],b&&(c[a]="tz"==a?b:1)}function ai(a,b,e){var d=window.moment||b.moment,c=b.returnFormat;if(a){if("moment"==c&&d)return d(a);if("locale"==c)return w(e,a,b);if("iso8601"==c)return aT(a,b.isoParts)}return a}function f(b,d,e,c){var a;return b?b.getTime?b:b.toDate?b.toDate():("string"==typeof b&&(b=b.trim()),(a=ak.exec(b))?(aO(a,2,c),new Date(1970,0,1,a[2]?+a[2]:0,a[3]?+a[3]:0,a[4]?+a[4]:0,a[5]?+a[5]:0)):(a||(a=al.exec(b)),a?(aO(a,0,c),new Date(a[1]?+a[1]:1970,a[2]?a[2]-1:0,a[3]?+a[3]:1,a[4]?+a[4]:0,a[5]?+a[5]:0,a[6]?+a[6]:0,a[7]?+a[7]:0)):R(d,b,e))):null}function $(a,b){return a.getFullYear()==b.getFullYear()&&a.getMonth()==b.getMonth()&&a.getDate()==b.getDate()}function aV(a){return a[0].innerWidth||a.innerWidth()}return k=k&&k.hasOwnProperty("default")?k.default:k,d=d||{},J={},an={},Q="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(a){return typeof a}:function(a){return a&&"function"==typeof Symbol&&a.constructor===Symbol&&a!==Symbol.prototype?"symbol":typeof a},ah=k.extend,ag={},d.$=k,k.mobiscroll=d,k.fn.mobiscroll=function(a){return ah(this,ag),aM(this,a,arguments)},D=[],n="undefined"!=typeof window,x=n?navigator.userAgent:"",aS=/Safari/.test(x),L=x.match(/Android|iPhone|iPad|iPod|Windows Phone|Windows|MSIE/i),s=n&&window.requestAnimationFrame||function(a){return setTimeout(a,20)},ab=n&&window.cancelAnimationFrame||function(a){clearTimeout(a)},/Android/i.test(L)?(g="android",u=x.match(/Android\s+([\d\.]+)/i),u&&(D=u[0].replace("Android ","").split("."))):/iPhone|iPad|iPod/i.test(L)?(g="ios",u=x.match(/OS\s+([\d\_]+)/i),u&&(D=u[0].replace(/_/g,".").replace("OS ","").split("."))):/Windows Phone/i.test(L)?g="wp":/Windows|MSIE/i.test(L)&&(g="windows"),j=D[0],W=D[1],F=0,X=void 0,n&&(["mouseover","mousedown","mouseup","click"].forEach(function(a){document.addEventListener(a,bb,!0)}),"android"==g&&j<5&&document.addEventListener("change",function(a){F&&"checkbox"==a.target.type&&!a.target.mbscChange&&(a.stopPropagation(),a.preventDefault()),delete a.target.mbscChange},!0)),d.uid="c5d09426",a=d.$,at=+new Date,B={},O={},ao={xsmall:0,small:576,medium:768,large:992,xlarge:1200},b=a.extend,b(J,{getCoord:e,preventClick:G,vibrate:aX}),q=b(d,{$:a,version:"4.8.2",autoTheme:"mobiscroll",themes:{form:{},page:{},frame:{},scroller:{},listview:{},navigation:{},progress:{},card:{}},platform:{name:g,majorVersion:j,minorVersion:W},i18n:{},instances:B,classes:O,util:J,settings:{},setDefaults:function(a){b(this.settings,a)},customTheme:function(g,e){var a,f=d.themes,c=["frame","scroller","listview","navigation","form","page","progress","card"];for(a=0;a<c.length;a++)f[c[a]][g]=b({},f[c[a]][e],{baseTheme:e})}}),aj=function(g,f){function n(d){var b,c;return e.responsive&&(c=d||o.offsetWidth,a.each(e.responsive,function(d,a){c>=(a.breakpoint||ao[d])&&(b=a)})),b}function r(){a(g).addClass("mbsc-comp"),g.id?B[g.id]&&B[g.id].destroy():g.id="mobiscroll"+ ++at,B[g.id]=d,d.__ready=!0}var o,p,j,i,e,m,h,l,k,d=this;d.settings={},d.element=g,d._init=c,d._destroy=c,d._processSettings=c,d._checkResp=function(b){if(d&&d._responsive){var a=n(b);if(i!==a)return i=a,d.init({}),!0}},d.init=function(c,r){var s,t;c&&d.getVal&&(t=d.getVal());for(s in d.settings)delete d.settings[s];e=d.settings,b(f,c),d._hasDef&&(k=q.settings),b(e,d._defaults,k,f),d._hasTheme&&(h=e.theme,"auto"!=h&&h||(h=q.autoTheme),"default"==h&&(h="mobiscroll"),f.theme=h,m=q.themes[d._class]?q.themes[d._class][h]:{}),d._hasLang&&(p=q.i18n[e.lang]),b(e,m,p,k,f),o=a(e.context)[0],d._responsive&&(i||(i=n()),b(e,i)),d._processSettings(i||{}),d._presets&&(j=d._presets[e.preset],j&&(j=j.call(g,d,f),b(e,j,f,i))),d._init(c),c&&d.setVal&&d.setVal(void 0===r?t:r,!0),l("onInit")},d.destroy=function(){d&&(d._destroy(),l("onDestroy"),delete B[g.id],d=null)},d.tap=function(a,b,c,e,f){ba(d,a,b,c,e,f)},d.trigger=function(c,h){var e,a,b,i=[k,m,j,f];for(a=0;a<4;a++)b=i[a],b&&b[c]&&(e=b[c].call(g,h||{},d));return e},d.option=function(a,g,h){var b={},c=["data","invalid","valid","readonly"];/calendar|eventcalendar|range/.test(e.preset)&&c.push("marked","labels","colors"),"object"===("undefined"==typeof a?"undefined":Q(a))?b=a:b[a]=g,c.forEach(function(a){f[a]=e[a]}),d.init(b,h)},d.getInst=function(){return d},f=f||{},l=d.trigger,d.__ready||r()},am=0,J.getJson=ac,V={},n&&(y=document.createElement("modernizr").style,p=aW(),m=p.replace(/^\-/,"").replace(/\-$/,"").replace("moz","Moz"),C=void 0!==y.animation?"animationend":"webkitAnimationEnd",ar=void 0!==y.transition,aq=void 0===y.touchAction||"ios"==g&&!aS&&(j<12||12==j&&W<2)),Y="position:absolute;left:0;top:0;",ad=Y+"right:0;bottom:0;overflow:hidden;z-index:-1;",az='<div style="'+ad+'"><div style="'+Y+'"></div></div><div style="'+ad+'"><div style="'+Y+'width:200%;height:200%;"></div></div>',M=d.themes,aB=/(iphone|ipod)/i.test(x)&&j>=7,ae="android"==g,aa="ios"==g,aE=aa&&8==j,aF=aa&&j>7,aG=function(a){a.preventDefault()},af="input,select,textarea,button",aI='textarea,button,input[type="button"],input[type="submit"]',aJ=af+',[tabindex="0"]',I=function(R,D,ah){function ai(b){t&&t.removeClass("mbsc-active"),t=a(this),t.hasClass("mbsc-disabled")||t.hasClass("mbsc-fr-btn-nhl")||t.addClass("mbsc-active"),"mousedown"===b.type?a(document).on("mouseup",B):"pointerdown"===b.type&&a(document).on("pointerup",B)}function B(b){t&&(t.removeClass("mbsc-active"),t=null),"mouseup"===b.type?a(document).off("mouseup",B):"pointerup"===b.type&&a(document).off("pointerup",B)}function X(c){d.activeInstance==b&&(13!=c.keyCode||a(c.target).is(aI)&&!c.shiftKey?27==c.keyCode&&b.cancel():b.select())}function ag(a){a||ae||!b._activeElm||(O=new Date,b._activeElm.focus())}function Z(e){var d=K,c=f.focusOnClose;b._markupRemove(),g.remove(),h&&(j.mbscModals--,f.scrollLock&&j.mbscLock--,j.mbscLock||z.removeClass("mbsc-fr-lock"),x&&(j.mbscIOSLock--,j.mbscIOSLock||(z.removeClass("mbsc-fr-lock-ios"),u.css({top:"",left:""}),r.scrollLeft(j.mbscScrollLeft),r.scrollTop(j.mbscScrollTop))),j.mbscModals||z.removeClass("mbsc-fr-lock-ctx"),j.mbscModals&&!N||e||(d||(d=l),setTimeout(function(){void 0===c||c===!0?(E=!0,d[0].focus()):c&&a(c)[0].focus()},200))),N=void 0,S=!1,m("onHide")}function ak(){clearTimeout(Y),Y=setTimeout(function(){b.position(!0)&&(w.style.visibility="hidden",w.offsetHeight,w.style.visibility="")},200)}function _(a){d.activeInstance==b&&a.target.nodeType&&!W.contains(a.target)&&new Date-O>100&&(O=new Date,b._activeElm.focus())}function $(i,d){function e(){g.off(C,e).removeClass("mbsc-anim-in mbsc-anim-trans mbsc-anim-trans-"+k).find(".mbsc-fr-popup").removeClass("mbsc-anim-"+k),ag(d)}if(h)g.appendTo(u);else if(l.is("div")&&!b._hasContent)l.empty().append(g);else if(l.hasClass("mbsc-control")){var c=l.closest(".mbsc-control-w");g.insertAfter(c),c.hasClass("mbsc-select")&&c.addClass("mbsc-select-inline")}else g.insertAfter(l);S=!0,b._markupInserted(g),m("onMarkupInserted",{target:q}),g.on("mousedown",".mbsc-btn-e,.mbsc-fr-btn-e",aG).on("touchstart mousedown",function(a){f.stopProp&&a.stopPropagation()}).on("keydown",".mbsc-fr-btn-e",function(a){32==a.keyCode&&(a.preventDefault(),a.stopPropagation(),this.click())}).on("keydown",function(b){if(32!=b.keyCode||a(b.target).is(af)){if(9==b.keyCode&&h&&f.focusTrap){var c=g.find(aJ).filter(function(){return this.offsetWidth>0||this.offsetHeight>0}),i=c.index(a(":focus",g)),d=c.length-1,e=0;b.shiftKey&&(d=0,e=-1),i===d&&(c.eq(e)[0].focus(),b.preventDefault())}}else b.preventDefault()}).on("touchstart mousedown pointerdown",".mbsc-fr-btn-e",ai).on("touchend",".mbsc-fr-btn-e",B),q.addEventListener("touchstart",function(){H||(H=!0,u.find(".mbsc-no-touch").removeClass("mbsc-no-touch"))},!0),a.each(p,function(d,c){b.tap(a(".mbsc-fr-btn"+d,g),function(a){c=o(c)?b.buttons[c]:c,(o(c.handler)?b.handlers[c.handler]:c.handler).call(this,a,b)},!0)}),b._attachEvents(g),b.position()!==!1&&((h||b._checkSize)&&(L=aU(q,ak,f.zone)),h&&(g.removeClass("mbsc-fr-pos"),k&&!i?g.addClass("mbsc-anim-in mbsc-anim-trans mbsc-anim-trans-"+k).on(C,e).find(".mbsc-fr-popup").addClass("mbsc-anim-"+k):ag(d)),m("onShow",{target:q,valueText:b._tempValue}))}function I(a,c){b._isVisible||(a&&a(),b.show()!==!1&&(K=c))}function aa(){b._fillValue(),m("onSet",{valueText:b._value})}function ab(){m("onCancel",{valueText:b._value})}function ac(){b.setVal(null,!0)}var u,ad,z,g,V,Q,y,r,U,p,t,j,k,s,h,S,P,q,v,n,J,x,L,W,w,Y,N,f,T,H,m,A,F,b=this,l=a(R),M=[],O=new Date;aj.call(this,R,D,!0),b.position=function(W){var z,N,M,L,t,K,I,H,B,G,V,x,c,d,R,P,C,k,p,O={},o=0,e=0,D=0,E=0;if(!S)return!1;if(P=A,R=F,c=q.offsetHeight,d=q.offsetWidth,d&&c&&(A!==d||F!==c||!W)){if(b._checkResp(d))return!1;if(A=d,F=c,b._isFullScreen||/top|bottom/.test(f.display)?y.width(d):h&&U.width(""),b._position(g),!b._isFullScreen&&/center|bubble/.test(f.display)&&(a(".mbsc-w-p",g).each(function(){C=this.getBoundingClientRect().width,E+=C,D=C>D?C:D}),x=E>d-16||f.tabs===!0,U.css({width:b._isLiquid?Math.min(f.maxPopupWidth,d-16):Math.ceil(x?D:E),"white-space":x?"":"nowrap"})),m("onPosition",{target:q,popup:w,hasTabs:x,oldWidth:P,oldHeight:R,windowWidth:d,windowHeight:c})!==!1&&h)return J&&(o=r.scrollLeft(),e=r.scrollTop(),A&&Q.css({width:"",height:""})),v=w.offsetWidth,n=w.offsetHeight,T=n<=c&&v<=d,"center"==f.display?(p=Math.max(0,o+(d-v)/2),k=Math.max(0,e+(c-n)/2)):"bubble"==f.display?(z=void 0===f.anchor?l:a(f.anchor),I=a(".mbsc-fr-arr-i",g)[0],L=z.offset(),t=L.top+(s?e-u.offset().top:0),K=L.left+(s?o-u.offset().left:0),N=z[0].offsetWidth,M=z[0].offsetHeight,H=I.offsetWidth,B=I.offsetHeight,p=i(K-(v-N)/2,o+3,o+d-v-3),k=t+M+B/2,k+n+8>e+c&&t-n-B/2>e?(y.removeClass("mbsc-fr-bubble-bottom").addClass("mbsc-fr-bubble-top"),k=t-n-B/2):y.removeClass("mbsc-fr-bubble-top").addClass("mbsc-fr-bubble-bottom"),a(".mbsc-fr-arr",g).css({left:i(K+N/2-(p+(v-H)/2),0,H)}),T=k>e&&p>o&&k+n<=e+c&&p+v<=o+d):(p=o,k="top"==f.display?e:Math.max(0,e+c-n)),J&&(G=Math.max(k+n,s?j.scrollHeight:a(document).height()),V=Math.max(p+v,s?j.scrollWidth:a(document).width()),Q.css({width:V,height:G}),f.scroll&&"bubble"==f.display&&(k+n+8>e+c||t>e+c||t+M<e)&&r.scrollTop(Math.min(t,k+n-c+8,G-c))),O.top=Math.floor(k),O.left=Math.floor(p),y.css(O),!0}},b.attachShow=function(g,e){var d,c=a(g).off(".mbsc"),h=c.prop("readonly");"inline"!==f.display&&((f.showOnFocus||f.showOnTap)&&c.is("input,select")&&(c.prop("readonly",!0).on("mousedown.mbsc",function(a){a.preventDefault()}).on("focus.mbsc",function(){b._isVisible&&this.blur()}),d=a('label[for="'+c.attr("id")+'"]'),d.length||(d=c.closest("label"))),c.is("select")||(f.showOnFocus&&c.on("focus.mbsc",function(){E?E=!1:I(e,c)}),f.showOnTap&&(c.on("keydown.mbsc",function(a){32!=a.keyCode&&13!=a.keyCode||(a.preventDefault(),a.stopPropagation(),I(e,c))}),b.tap(c,function(a){a.isMbscTap&&(H=!0),I(e,c)}),d&&d.length&&b.tap(d,function(a){a.preventDefault(),a.target!==c[0]&&I(e,c)}))),M.push({readOnly:h,el:c,lbl:d}))},b.select=function(){h?b.hide(!1,"set",!1,aa):aa()},b.cancel=function(){h?b.hide(!1,"cancel",!1,ab):ab()},b.clear=function(){b._clearValue(),m("onClear"),h&&b._isVisible&&!b.live?b.hide(!1,"clear",!1,ac):ac()},b.enable=function(){f.disabled=!1,a.each(M,function(b,a){a.el.is("input,select")&&(a.el[0].disabled=!1)})},b.disable=function(){f.disabled=!0,a.each(M,function(b,a){a.el.is("input,select")&&(a.el[0].disabled=!0)})},b.show=function(C,L){var v,i,I,D,c,n,B,O,t,l,E,M;if(!f.disabled&&!b._isVisible){if(b._readValue(),m("onBeforeShow")===!1)return!1;if(K=null,k=f.animate,p=f.buttons||[],J=s||"bubble"==f.display,x=aB&&!J&&f.scrollLock,v=p.length>0,k!==!1&&("top"==f.display?k=k||"slidedown":"bottom"==f.display?k=k||"slideup":"center"!=f.display&&"bubble"!=f.display||(k=k||"pop")),h&&(A=0,F=0,x&&!z.hasClass("mbsc-fr-lock-ios")&&(j.mbscScrollTop=D=Math.max(0,r.scrollTop()),j.mbscScrollLeft=I=Math.max(0,r.scrollLeft()),u.css({top:-D+"px",left:-I+"px"})),z.addClass((f.scrollLock?"mbsc-fr-lock":"")+(x?" mbsc-fr-lock-ios":"")+(s?" mbsc-fr-lock-ctx":"")),a(document.activeElement).is("input,textarea")&&document.activeElement.blur(),N=d.activeInstance,d.activeInstance=b,j.mbscModals=(j.mbscModals||0)+1,x&&(j.mbscIOSLock=(j.mbscIOSLock||0)+1),f.scrollLock&&(j.mbscLock=(j.mbscLock||0)+1)),i='<div lang="'+f.lang+'" class="mbsc-fr mbsc-'+f.theme+(f.baseTheme?" mbsc-"+f.baseTheme:"")+" mbsc-fr-"+f.display+" "+(f.cssClass||"")+" "+(f.compClass||"")+(b._isLiquid?" mbsc-fr-liq":"")+(h?" mbsc-fr-pos"+(f.showOverlay?"":" mbsc-fr-no-overlay"):"")+(P?" mbsc-fr-pointer":"")+(aF?" mbsc-fr-hb":"")+(H?"":" mbsc-no-touch")+(x?" mbsc-platform-ios":"")+(v?p.length>=3?" mbsc-fr-btn-block ":"":" mbsc-fr-nobtn")+'">'+(h?'<div class="mbsc-fr-persp">'+(f.showOverlay?'<div class="mbsc-fr-overlay"></div>':"")+'<div role="dialog" class="mbsc-fr-scroll">':"")+'<div class="mbsc-fr-popup'+(f.rtl?" mbsc-rtl":" mbsc-ltr")+(f.headerText?" mbsc-fr-has-hdr":"")+'">'+("bubble"===f.display?'<div class="mbsc-fr-arr-w"><div class="mbsc-fr-arr-i"><div class="mbsc-fr-arr"></div></div></div>':"")+(h?'<div class="mbsc-fr-focus" tabindex="-1"></div>':"")+'<div class="mbsc-fr-w">'+(f.headerText?'<div class="mbsc-fr-hdr">'+(o(f.headerText)?f.headerText:"")+"</div>":"")+'<div class="mbsc-fr-c">',i+=b._generateContent(),i+="</div>",v){O=p.length;for(i+='<div class="mbsc-fr-btn-cont">',n=0;n<p.length;n++)B=f.btnReverse?O-n-1:n,c=p[B],c=o(c)?b.buttons[c]:c,"set"===c.handler&&(c.parentClass="mbsc-fr-btn-s"),"cancel"===c.handler&&(c.parentClass="mbsc-fr-btn-c"),i+="<div"+(f.btnWidth?' style="width:'+100/p.length+'%"':"")+' class="mbsc-fr-btn-w '+(c.parentClass||"")+'"><div tabindex="0" role="button" class="mbsc-fr-btn'+B+" mbsc-fr-btn-e "+(void 0===c.cssClass?f.btnClass:c.cssClass)+(c.icon?" mbsc-ic mbsc-ic-"+c.icon:"")+'">'+(c.text||"")+"</div></div>";i+="</div>"}i+="</div></div></div></div>"+(h?"</div></div>":""),g=a(i),Q=a(".mbsc-fr-persp",g),V=a(".mbsc-fr-scroll",g),U=a(".mbsc-fr-w",g),y=a(".mbsc-fr-popup",g),ad=a(".mbsc-fr-hdr",g),q=g[0],W=V[0],w=y[0],b._activeElm=a(".mbsc-fr-focus",g)[0],b._markup=g,b._isVisible=!0,b.markup=q,b._markupReady(g),m("onMarkupReady",{target:q}),h&&(a(window).on("keydown",X),f.scrollLock&&g.on("touchmove mousewheel wheel",function(a){T&&a.preventDefault()}),f.focusTrap&&r.on("focusin",_),f.closeOnOverlayTap)&&V.on("touchstart mousedown",function(a){l||a.target!=W||(l=!0,t=!1,E=e(a,"X"),M=e(a,"Y"))}).on("touchmove mousemove",function(a){l&&!t&&(Math.abs(e(a,"X")-E)>9||Math.abs(e(a,"Y")-M)>9)&&(t=!0)}).on("touchcancel",function(){l=!1}).on("touchend click",function(a){l&&!t&&(b.cancel(),"touchend"==a.type&&G()),l=!1}),h&&x?setTimeout(function(){$(C,L)},100):$(C,L)}},b.hide=function(c,e,f,i){function j(){g.off(C,j),Z(c)}return!(!b._isVisible||!f&&!b._isValid&&"set"==e||!f&&m("onBeforeClose",{valueText:b._tempValue,button:e})===!1)&&(b._isVisible=!1,L&&(L.detach(),L=null),h&&(a(document.activeElement).is("input,textarea")&&w.contains(document.activeElement)&&document.activeElement.blur(),d.activeInstance==b&&(d.activeInstance=N),a(window).off("keydown",X),r.off("focusin",_)),g&&(h&&k&&!c?g.addClass("mbsc-anim-out mbsc-anim-trans mbsc-anim-trans-"+k).on(C,j).find(".mbsc-fr-popup").addClass("mbsc-anim-"+k):Z(c),b._detachEvents(g)),i&&i(),l.trigger("blur"),void m("onClose",{valueText:b._value}))},b.isVisible=function(){return b._isVisible},b.setVal=c,b.getVal=c,b._generateContent=c,b._attachEvents=c,b._detachEvents=c,b._readValue=c,b._clearValue=c,b._fillValue=c,b._markupReady=c,b._markupInserted=c,b._markupRemove=c,b._position=c,b.__processSettings=c,b.__init=c,b.__destroy=c,b._destroy=function(){b.hide(!0,!1,!0),l.off(".mbsc"),a.each(M,function(b,a){a.el.off(".mbsc").prop("readonly",a.readOnly),a.lbl&&a.lbl.off(".mbsc")}),b.__destroy()},b._updateHeader=function(){var a=f.headerText,c=a?"function"==typeof a?a.call(R,b._tempValue):a.replace(/\{value\}/i,b._tempValue):"";ad.html(c||"&nbsp;")},b._processSettings=function(c){var d,e;for(b.__processSettings(c),P=!f.touchUi,P&&(f.display=c.display||D.display||"bubble",f.buttons=c.buttons||D.buttons||[],f.showOverlay=c.showOverlay||D.showOverlay||!1),f.buttons=f.buttons||("inline"!==f.display?["cancel","set"]:[]),f.headerText=void 0===f.headerText?"inline"!==f.display&&"{value}":f.headerText,p=f.buttons||[],h="inline"!==f.display,s="body"!=f.context,u=a(f.context),z=s?u:a("body,html"),j=u[0],b._$window=r=a(s?f.context:window),b.live=!0,e=0;e<p.length;e++)d=p[e],"ok"!=d&&"set"!=d&&"set"!=d.handler||(b.live=!1);b.buttons.set={text:f.setText,icon:f.setIcon,handler:"set"},b.buttons.cancel={text:f.cancelText,icon:f.cancelIcon,handler:"cancel"},b.buttons.close={text:f.closeText,icon:f.closeIcon,handler:"cancel"},b.buttons.clear={text:f.clearText,icon:f.clearIcon,handler:"clear"},b._isInput=l.is("input")},b._init=function(c){var a=b._isVisible,d=a&&!g.hasClass("mbsc-fr-pos");a&&b.hide(!0,!1,!0),l.off(".mbsc"),b.__init(c),b._isLiquid="liquid"==f.layout,h?(b._readValue(),b._hasContent||f.skipShow||b.attachShow(l),a&&b.show(d)):b.show(),l.removeClass("mbsc-cloak").filter("input, select, textarea").on("change.mbsc",function(){b._preventChange||b.setVal(l.val(),!0,!1),b._preventChange=!1})},b.buttons={},b.handlers={set:b.select,cancel:b.cancel,clear:b.clear},b._value=null,b._isValid=!0,b._isVisible=!1,f=b.settings,m=b.trigger,ah||b.init()},I.prototype._defaults={lang:"en",setText:"Set",selectedText:"{count} selected",closeText:"Close",cancelText:"Cancel",clearText:"Clear",context:"body",maxPopupWidth:600,disabled:!1,closeOnOverlayTap:!0,showOnFocus:ae||aa,showOnTap:!0,display:"center",scroll:!0,scrollLock:!0,showOverlay:!0,tap:!0,touchUi:!0,btnClass:"mbsc-fr-btn",btnWidth:!0,focusTrap:!0,focusOnClose:!aE},O.Frame=I,M.frame.mobiscroll={headerText:!1,btnWidth:!1},M.scroller.mobiscroll=b({},M.frame.mobiscroll,{rows:5,showLabel:!1,selectedLineBorder:1,weekDays:"min",checkIcon:"ion-ios7-checkmark-empty",btnPlusClass:"mbsc-ic mbsc-ic-arrow-down5",btnMinusClass:"mbsc-ic mbsc-ic-arrow-up5",btnCalPrevClass:"mbsc-ic mbsc-ic-arrow-left5",btnCalNextClass:"mbsc-ic mbsc-ic-arrow-right5"}),n&&a(window).on("focus",function(){K&&(E=!0)}),aL="ios"==g,T=function(I,ar,av){function aq(f){u("onStart",{domEvent:f}),b.stopProp&&f.stopPropagation(),b.prevDef&&f.preventDefault(),b.readonly||b.lock&&C||aD(f,this)&&!G&&(w&&w.removeClass("mbsc-active"),L=!1,C||(w=a(f.target).closest(".mbsc-btn-e",this),w.length&&!w.hasClass("mbsc-disabled")&&(L=!0,aa=setTimeout(function(){w.addClass("mbsc-active")},100))),G=!0,x=!1,N=!1,h.scrolled=C,V=e(f,"X"),M=e(f,"Y"),X=V,S=0,K=0,d=0,au=new Date,n=+aH(z,c)||0,C&&Q(n,aL?0:1),"mousedown"===f.type&&a(document).on("mousemove",U).on("mouseup",J))}function U(a){G&&(b.stopProp&&a.stopPropagation(),X=e(a,"X"),ag=e(a,"Y"),S=X-V,K=ag-M,d=c?K:S,L&&(Math.abs(K)>b.thresholdY||Math.abs(S)>b.thresholdX)&&(clearTimeout(aa),w.removeClass("mbsc-active"),L=!1),(h.scrolled||!N&&Math.abs(d)>ah)&&(x||u("onGestureStart",g),h.scrolled=x=!0,B||(B=!0,T=s(ao))),c||b.scrollLock?a.preventDefault():h.scrolled?a.preventDefault():Math.abs(K)>7&&(N=!0,h.scrolled=!0,J()))}function ao(){v&&(d=i(d,-f*v,f*v)),Q(i(n+d,q-H,k+H)),B=!1}function J(c){if(G){var e,f=new Date-au;b.stopProp&&c&&c.stopPropagation(),ab(T),B=!1,!N&&h.scrolled&&(b.momentum&&f<300&&(e=d/f,d=Math.max(Math.abs(d),e*e/b.speedUnit)*(d<0?-1:1)),ae(d)),L&&(clearTimeout(aa),w.addClass("mbsc-active"),setTimeout(function(){w.removeClass("mbsc-active")},100),N||h.scrolled||u("onBtnTap",{target:w[0],domEvent:c})),c&&"mouseup"==c.type&&a(document).off("mousemove",U).off("mouseup",J),G=!1}}function am(a){if(a=a.originalEvent||a,d=c?void 0==a.deltaY?a.wheelDelta||a.detail:a.deltaY:a.deltaX,u("onStart",{domEvent:a}),b.stopProp&&a.stopPropagation(),d){if(a.preventDefault(),a.deltaMode&&1==a.deltaMode&&(d*=15),d=i(-d,-A,A),n=j,b.readonly)return;if(x||ak(),n+d<q&&(n=q,d=0),n+d>k&&(n=k,d=0),B||(B=!0,T=s(ao)),!d&&x)return;x=!0,clearTimeout(an),an=setTimeout(function(){ab(T),B=!1,x=!1,ae(d)},200)}}function at(c){u("onStart",{domEvent:c}),b.readonly||(c.stopPropagation(),n=j,x=!1,c.target==y?(M=e(c,"Y",!0),a(document).on("mousemove",af).on("mouseup",ac)):(M=Z.offset().top,af(c),ac()))}function af(b){var a=(e(b,"Y",!0)-M)/r;ad?(d=-(v*f*2+r)*a,d=i(d,-f*v,f*v)):d=(q-k-r)*a,x||ak(),x=!0,Q(i(n+d,q-H,k+H))}function ac(){n=j,ae(0),a(document).off("mousemove",af).off("mouseup",ac)}function ai(a){a.stopPropagation()}function ak(){g={posX:c?0:j,posY:c?j:0,originX:c?0:n,originY:c?n:0,direction:d>0?c?270:360:c?90:180},u("onGestureStart",g)}function ae(e){var a,l,d;if(v&&(e=i(e,-f*v,f*v)),d=i(Math.round((n+e)/f)*f,q,k),t){if(e<0){for(a=t.length-1;a>=0;a--)if(Math.abs(d)+r>=t[a].breakpoint){E=a,W=2,d=t[a].snap2;break}}else if(e>=0)for(a=0;a<t.length;a++)if(Math.abs(d)<=t[a].breakpoint){E=a,W=1,d=t[a].snap1;break}d=i(d,q,k)}l=b.time||(j<q||j>k?1e3:Math.max(1e3,Math.abs(d-j)*b.timeUnit)),g.destinationX=c?0:d,g.destinationY=c?d:0,g.duration=l,g.transitionTiming=O,u("onGestureEnd",g),h.scroll(d,l)}function Q(a,d,t,h){var i,l=a!=j,o=d>1,s=d?p+"transform "+Math.round(d)+"ms "+O:"",e=function(){clearInterval(R),clearTimeout(Y),C=!1,j=a,g.posX=c?0:a,g.posY=c?a:0,l&&u("onMove",g),o&&u("onAnimationEnd",g),h&&h()};g={posX:c?0:j,posY:c?j:0,originX:c?0:n,originY:c?n:0,direction:a-j>0?c?270:360:c?90:180},j=a,o&&(g.destinationX=c?0:a,g.destinationY=c?a:0,g.duration=d,g.transitionTiming=O,u("onAnimationStart",g)),$[m+"Transition"]=s,$[m+"Transform"]="translate3d("+(c?"0,"+a+"px,":a+"px,0,")+"0)",y&&D&&(i=ad?(ap-a)/(v*f*2):(a-k)/(q-k),y.style[m+"Transition"]=s,y.style[m+"Transform"]="translate3d(0,"+Math.max(0,Math.min((r-D)*i,r-D))+"px,0)"),!l&&!C||!d||d<=1?e():d&&(C=!t,clearInterval(R),R=setInterval(function(){var b=+aH(z,c)||0;g.posX=c?0:b,g.posY=c?b:0,u("onMove",g),Math.abs(b-a)<2&&e()},100),clearTimeout(Y),Y=setTimeout(function(){e()},d)),b.sync&&b.sync(a,d,O)}var w,Z,P,aa,r,S,K,d,_,O,H,X,ag,g,L,ad,k,v,q,G,C,N,T,B,y,D,ap,al,x,an,A,R,f,t,n,au,V,M,$,z,Y,ah,u,c,j,h=this,E=0,W=1,b=ar,F=a(I);aj.call(this,I,ar,!0),h.scrolled=!1,h.scroll=function(b,c,d,e){b=l(b)?Math.round(b/f)*f:Math.ceil((a(b,I).length?Math.round(z.offset()[_]-a(b,I).offset()[_]):j)/f)*f,b=i(b,q,k),E=Math.round(b/f),n=j,ap=v*f+b,Q(b,c,d,e)},h.refresh=function(d){var a;for(r=(void 0===b.contSize?c?F.height():F.width():b.contSize)||0,k=(void 0===b.maxScroll?0:b.maxScroll)||0,q=Math.min(k,void 0===b.minScroll?Math.min(0,c?r-z.height():r-z.width()):b.minScroll)||0,t=null,!c&&b.rtl&&(a=k,k=-q,q=-a),o(b.snap)&&(t=[],z.find(b.snap).each(function(){var a=c?this.offsetTop:this.offsetLeft,b=c?this.offsetHeight:this.offsetWidth;t.push({breakpoint:a+b/2,snap1:-a,snap2:r-a-b})})),f=l(b.snap)?b.snap:1,v=b.snap?b.maxSnapScroll:0,O=b.easing,H=b.elastic?l(b.snap)?f:l(b.elastic)?b.elastic:0:0,A=f;A>44;)A/=2;A=Math.round(44/A)*A,y&&(ad=q==-(1/0)||k==1/0,D=q<k?Math.max(20,r*r/(k-q+r)):0,y.style.height=D+"px",al.style.height=D?"":0),void 0===j&&(j=b.initialPos,E=Math.round(j/f)),d||h.scroll(b.snap?t?t[E]["snap"+W]:E*f:j)},h._processSettings=function(){c="Y"==b.axis,_=c?"top":"left",z=b.moveElement||F.children().eq(0),$=z[0].style,ah=c?b.thresholdY:b.thresholdX,b.scrollbar&&(P=b.scrollbar,Z=P.find(".mbsc-sc-bar"),y=Z[0],al=P[0])},h._init=function(){h.refresh(),F.on("touchstart mousedown",aq).on("touchmove",U).on("touchend touchcancel",J),b.mousewheel&&F.on("wheel mousewheel",am),y&&P.on("mousedown",at).on("click",ai),I.addEventListener("click",function(a){h.scrolled&&(h.scrolled=!1,a.stopPropagation(),a.preventDefault())},!0)},h._destroy=function(){clearInterval(R),F.off("touchstart mousedown",aq).off("touchmove",U).off("touchend touchcancel",J).off("wheel mousewheel",am),y&&P.off("mousedown",at).off("click",ai)},b=h.settings,u=h.trigger,av||h.init()},T.prototype={_defaults:{speedUnit:.0022,timeUnit:3,initialPos:0,axis:"Y",thresholdX:10,thresholdY:5,easing:"cubic-bezier(0.190, 1.000, 0.220, 1.000)",stopProp:!0,momentum:!0,mousewheel:!0,elastic:!0}},N={},P=n?window.CSS:null,aP=P&&P.supports&&P.supports("(transform-style: preserve-3d)"),H=function(q,K,W){function U(b){var c,d,e=+a(this).attr("data-index");38==b.keyCode?(c=!0,d=-1):40==b.keyCode?(c=!0,d=1):32==b.keyCode&&(c=!0,Q(e,a(b.target))),c&&(b.stopPropagation(),b.preventDefault(),d&&v.start(e,d,b))}function V(){v.stop()}function Q(b,f){var a=h[b],i=+f.attr("data-index"),e=s(a,i),g=c._tempSelected[b],k=l(a.multiple)?a.multiple:1/0;j("onItemTap",{target:f[0],index:b,value:e,selected:f.hasClass("mbsc-sc-itm-sel")})!==!1&&(a.multiple&&!a._disabled[e]&&(void 0!==g[e]?(f.removeClass(o).removeAttr("aria-selected"),delete g[e]):(1==k&&(c._tempSelected[b]=g={},a._$markup.find(".mbsc-sc-itm-sel").removeClass(o).removeAttr("aria-selected")),S(g).length<k&&(f.addClass(o).attr("aria-selected","true"),g[e]=e))),H(a,b,i,N,a._index<i?1:2,!0,a.multiple),c.live&&(!a.multiple||1===a.multiple&&d.tapSelect)&&(d.setOnTap===!0||d.setOnTap[b])&&setTimeout(function(){c.select()},d.tapSelect?0:200))}function R(b,c){var a=h[b];return a&&(!a.multiple||1!==a.multiple&&c&&(d.setOnTap===!0||d.setOnTap[b]))}function P(a){return-(a.max-a._offset-(a.multiple&&!g?Math.floor(d.rows/2):0))*e}function O(a){return-(a.min-a._offset+(a.multiple&&!g?Math.floor(d.rows/2):0))*e}function B(a,b){return(a._array?a._map[b]:+a.getIndex(b,c))||0}function L(b,d){var e=b.data;if(d>=b.min&&d<=b.max)return b._array?b.circular?a(e).get(d%b._length):e[d]:a.isFunction(e)?e(d,c):""}function x(b){return a.isPlainObject(b)?void 0!==b.value?b.value:b.display:b}function Y(b){var c=a.isPlainObject(b)?b.display:b;return void 0===c?"":c}function s(a,b){return x(L(a,b))}function _(a,b,e){var c=h[a];H(c,a,c._index+b,d.delay+100,1==b?1:2,!1,!1,"keydown"==e.type)}function M(b){return a.isArray(d.readonly)?d.readonly[b]:d.readonly}function G(c,g,j){var h=c._index-c._batch;return c.data=c.data||[],c.key=void 0!==c.key?c.key:g,c.label=void 0!==c.label?c.label:g,c._map={},c._array=a.isArray(c.data),c._array&&(c._length=c.data.length,a.each(c.data,function(a,b){c._map[x(b)]=a})),c.circular=void 0===d.circular?void 0===c.circular?c._array&&c._length>d.rows:c.circular:a.isArray(d.circular)?d.circular[g]:d.circular,c.min=c._array?c.circular?-(1/0):0:void 0===c.min?-(1/0):c.min,c.max=c._array?c.circular?1/0:c._length-1:void 0===c.max?1/0:c.max,c._nr=g,c._index=B(c,f[g]),c._disabled={},c._batch=0,c._current=c._index,c._first=c._index-i,c._last=c._index+i,c._offset=c._first,j?(c._offset-=c._margin/e+(c._index-h),c._margin+=(c._index-h)*e):c._margin=0,c._refresh=function(a){b(c._scroller.settings,{minScroll:P(c),maxScroll:O(c)}),c._scroller.refresh(a)},D[c.key]=c,c}function n(h,m,v,t,q){var g,r,a,b,n,i,k,j,l="",s=c._tempSelected[m],w=h._disabled||{};for(g=v;g<=t;g++)a=L(h,g),n=Y(a),b=x(a),r=a&&void 0!==a.cssClass?a.cssClass:"",i=a&&void 0!==a.label?a.label:"",k=a&&a.invalid,j=void 0!==b&&b==f[m]&&!h.multiple,l+='<div role="option" tabindex="-1" aria-selected="'+!!s[b]+'" class="mbsc-sc-itm '+(q?"mbsc-sc-itm-3d ":"")+r+" "+(j?"mbsc-sc-itm-sel ":"")+(s[b]?o:"")+(void 0===b?" mbsc-sc-itm-ph":" mbsc-btn-e")+(k?" mbsc-sc-itm-inv-h mbsc-disabled":"")+(w[b]?" mbsc-sc-itm-inv mbsc-disabled":"")+'" data-index="'+g+'" data-val="'+Z(b)+'"'+(i?' aria-label="'+i+'"':"")+(j?' aria-selected="true"':"")+' style="height:'+e+"px;line-height:"+e+"px;"+(q?p+"transform:rotateX("+(h._offset-g)*E%360+"deg) translateZ("+e*d.rows/2+"px);":"")+'">'+(u>1?'<div class="mbsc-sc-itm-ml" style="line-height:'+Math.round(e/u)+"px;font-size:"+Math.round(e/u*.8)+'px;">':"")+n+(u>1?"</div>":"")+"</div>";return l}function X(b,l,o){var m=Math.round(-o/e)+b._offset,c=m-b._current,d=b._first,f=b._last,h=d+i-k+1,j=f-i+k;c&&(b._first+=c,b._last+=c,b._current=m,c>0?(b._$scroller.append(n(b,l,Math.max(f+1,d+c),f+c)),a(".mbsc-sc-itm",b._$scroller).slice(0,Math.min(c,f-d+1)).remove(),g&&(b._$3d.append(n(b,l,Math.max(j+1,h+c),j+c,!0)),a(".mbsc-sc-itm",b._$3d).slice(0,Math.min(c,j-h+1)).attr("class","mbsc-sc-itm-del"))):c<0&&(b._$scroller.prepend(n(b,l,d+c,Math.min(d-1,f+c))),a(".mbsc-sc-itm",b._$scroller).slice(Math.max(c,d-f-1)).remove(),g&&(b._$3d.prepend(n(b,l,h+c,Math.min(h-1,j+c),!0)),a(".mbsc-sc-itm",b._$3d).slice(Math.max(c,h-j-1)).attr("class","mbsc-sc-itm-del"))),b._margin+=c*e,b._$scroller.css("margin-top",b._margin+"px"))}function J(n,m,k,l){var d,a=h[n],i=l||a._disabled,b=B(a,m),e=s(a,b),j=e,g=e,c=0,f=0;if(i[e]===!0){for(d=0;b-c>=a.min&&i[j]&&d<100;)d++,c++,j=s(a,b-c);for(d=0;b+f<a.max&&i[g]&&d<100;)d++,f++,g=s(a,b+f);e=(f<c&&f&&2!==k||!c||b-c<0||1==k)&&!i[g]?g:j}return e}function z(t,b,u,l,y,v,x){var p,m,s,k,n=c._isVisible;r=!0,k=d.validate.call(q,{values:f.slice(0),index:b,direction:u},c)||{},r=!1,k.valid&&(c._tempWheelArray=f=k.valid.slice(0)),v||a.each(h,function(h,d){if(n&&d._$markup.find(".mbsc-sc-itm-inv").removeClass("mbsc-sc-itm-inv mbsc-disabled"),d._disabled={},k.disabled&&k.disabled[h]&&a.each(k.disabled[h],function(b,a){d._disabled[a]=!0,n&&d._$markup.find('.mbsc-sc-itm[data-val="'+Z(a)+'"]').addClass("mbsc-sc-itm-inv mbsc-disabled")}),f[h]=d.multiple?f[h]:J(h,f[h],u),n){if(d.multiple&&void 0!==b||d._$markup.find(".mbsc-sc-itm-sel").removeClass(o).removeAttr("aria-selected"),m=B(d,f[h]),p=m-d._index+d._batch,Math.abs(p)>2*i+1&&(s=p+(2*i+1)*(p>0?-1:1),d._offset+=s,d._margin-=s*e,d._refresh()),d._index=m+d._batch,d.multiple){if(void 0===b)for(var j in c._tempSelected[h])d._$markup.find('.mbsc-sc-itm[data-val="'+Z(j)+'"]').addClass(o).attr("aria-selected","true")}else d._$markup.find('.mbsc-sc-itm[data-val="'+Z(f[h])+'"]').addClass("mbsc-sc-itm-sel").attr("aria-selected","true");d._$active&&d._$active.attr("tabindex",-1),d._$active=d._$markup.find('.mbsc-sc-itm[data-index="'+d._index+'"]').eq(g&&d.multiple?1:0).attr("tabindex",0),x&&b===h&&d._$active.length&&(d._$active[0].focus(),d._$scroller.parent().scrollTop(0)),d._scroller.scroll(-(m-d._offset+d._batch)*e,b===h||void 0===b?t:N,y)}}),j("onValidated",{index:b,time:t}),c._tempValue=d.formatValue.call(q,f,c),n&&c._updateHeader(),c.live&&R(b,v)&&(c._hasValue=l||c._hasValue,w(l,l,0,!0),l&&j("onSet",{valueText:c._value})),l&&j("onChange",{index:b,valueText:c._tempValue})}function H(a,c,b,e,g,h,i,j){var d=s(a,b);void 0!==d&&(f[c]=d,a._batch=a._array?Math.floor(b/a._length)*a._length:0,a._index=b,setTimeout(function(){z(e,c,g,!0,h,i,j)},10))}function w(g,e,i,k,l){if(k?c._tempValue=d.formatValue.call(q,c._tempWheelArray,c):z(i),!l){c._wheelArray=[];for(var a=0;a<f.length;a++)c._wheelArray[a]=h[a]&&h[a].multiple?Object.keys(c._tempSelected[a]||{})[0]:f[a];c._value=c._hasValue?c._tempValue:null,c._selected=b(!0,{},c._tempSelected)}g&&(c._isInput&&F.val(c._hasValue?c._tempValue:""),j("onFill",{valueText:c._hasValue?c._tempValue:"",change:e}),e&&(c._preventChange=!0,F.trigger("change")))}var C,k,E,g,o,t,v,f,e,y,A,r,d,j,u,h,D,i=40,N=1e3,c=this,F=a(q);I.call(this,q,K,!0),c.setVal=c._setVal=function(b,e,g,h,i){c._hasValue=null!==b&&void 0!==b,c._tempWheelArray=f=a.isArray(b)?b.slice(0):d.parseValue.call(q,b,c)||[],w(e,void 0===g?e:g,i,!1,h)},c.getVal=c._getVal=function(b){var a=c._hasValue||b?c[b?"_tempValue":"_value"]:null;return l(a)?+a:a},c.setArrayVal=c.setVal,c.getArrayVal=function(a){return a?c._tempWheelArray:c._wheelArray},c.changeWheel=function(f,h,j){var e,d;a.each(f,function(a,f){d=D[a],d&&(e=d._nr,b(d,f),G(d,e,!0),c._isVisible&&(g&&d._$3d.html(n(d,e,d._first+i-k+1,d._last-i+k,!0)),d._$scroller.html(n(d,e,d._first,d._last)).css("margin-top",d._margin+"px"),d._refresh(r)))}),!c._isVisible||c._isLiquid||r||c.position(),r||z(h,void 0,void 0,j)},c.getValidValue=J,c._generateContent=function(){var l,m=0,j="",o=g?p+"transform: translateZ("+(e*d.rows/2+3)+"px);":"",q='<div class="mbsc-sc-whl-l" style="'+o+"height:"+e+"px;margin-top:-"+(e/2+(d.selectedLineBorder||0))+'px;"></div>',f=0;return a.each(d.wheels,function(r,p){j+='<div class="mbsc-w-p mbsc-sc-whl-gr-c'+(g?" mbsc-sc-whl-gr-3d-c":"")+(d.showLabel?" mbsc-sc-lbl-v":"")+'">'+q+'<div class="mbsc-sc-whl-gr'+(g?" mbsc-sc-whl-gr-3d":"")+(t?" mbsc-sc-cp":"")+(d.width||d.maxWidth?'"':'" style="max-width:'+d.maxPopupWidth+'px;"')+">",a.each(p,function(p,a){c._tempSelected[f]=b({},c._selected[f]),h[f]=G(a,f),m+=d.maxWidth?d.maxWidth[f]||d.maxWidth:d.width?d.width[f]||d.width:0,l=void 0!==a.label?a.label:p,j+='<div class="mbsc-sc-whl-w '+(a.cssClass||"")+(a.multiple?" mbsc-sc-whl-multi":"")+'" style="'+(d.width?"width:"+(d.width[f]||d.width)+"px;":(d.minWidth?"min-width:"+(d.minWidth[f]||d.minWidth)+"px;":"")+(d.maxWidth?"max-width:"+(d.maxWidth[f]||d.maxWidth)+"px;":""))+'">'+(A?'<div class="mbsc-sc-bar-c"><div class="mbsc-sc-bar"></div></div>':"")+'<div class="mbsc-sc-whl-o" style="'+o+'"></div>'+q+'<div aria-live="off" aria-label="'+l+'"'+(a.multiple?' aria-multiselectable="true"':"")+' role="listbox" data-index="'+f+'" class="mbsc-sc-whl" style="height:'+d.rows*e*(g?1.1:1)+'px;">'+(t?'<div data-index="'+f+'" data-step="1" class="mbsc-sc-btn mbsc-sc-btn-plus '+(d.btnPlusClass||"")+'"></div><div data-index="'+f+'" data-step="-1" class="mbsc-sc-btn mbsc-sc-btn-minus '+(d.btnMinusClass||"")+'"></div>':"")+'<div class="mbsc-sc-lbl">'+l+'</div><div class="mbsc-sc-whl-c" style="height:'+y+"px;margin-top:-"+(y/2+1)+"px;"+o+'"><div class="mbsc-sc-whl-sc" style="top:'+(y-e)/2+'px;">',j+=n(a,f,a._first,a._last)+"</div></div>",g&&(j+='<div class="mbsc-sc-whl-3d" style="height:'+e+"px;margin-top:-"+e/2+'px;">',j+=n(a,f,a._first+i-k+1,a._last-i+k,!0),j+="</div>"),j+="</div></div>",f++}),j+="</div></div>"}),m&&(d.maxPopupWidth=m),j},c._attachEvents=function(b){v=ay(a(".mbsc-sc-btn",b),_,d.delay,M,!0),a(".mbsc-sc-whl",b).on("keydown",U).on("keyup",V)},c._detachEvents=function(){v.stop();for(var a=0;a<h.length;a++)h[a]._scroller.destroy()},c._markupReady=function(b){C=b,a(".mbsc-sc-whl-w",C).each(function(c){var k,f=a(this),b=h[c];b._$markup=f,b._$scroller=a(".mbsc-sc-whl-sc",this),b._$3d=a(".mbsc-sc-whl-3d",this),b._scroller=new T(this,{mousewheel:d.mousewheel,moveElement:b._$scroller,scrollbar:a(".mbsc-sc-bar-c",this),initialPos:(b._first-b._index)*e,contSize:d.rows*e,snap:e,minScroll:P(b),maxScroll:O(b),maxSnapScroll:i,prevDef:!0,stopProp:!0,timeUnit:3,easing:"cubic-bezier(0.190, 1.000, 0.220, 1.000)",sync:function(c,a,d){var f=a?p+"transform "+Math.round(a)+"ms "+d:"";g&&(b._$3d[0].style[m+"Transition"]=f,b._$3d[0].style[m+"Transform"]="rotateX("+-c/e*E+"deg)")},onStart:function(b,a){a.settings.readonly=M(c)},onGestureStart:function(){f.addClass("mbsc-sc-whl-a mbsc-sc-whl-anim"),j("onWheelGestureStart",{index:c})},onGestureEnd:function(a){var d=90==a.direction?1:2,f=a.duration,g=a.destinationY;k=Math.round(-g/e)+b._offset,H(b,c,k,f,d)},onAnimationStart:function(){f.addClass("mbsc-sc-whl-anim")},onAnimationEnd:function(){f.removeClass("mbsc-sc-whl-a mbsc-sc-whl-anim"),j("onWheelAnimationEnd",{index:c}),b._$3d.find(".mbsc-sc-itm-del").remove()},onMove:function(a){X(b,c,a.posY)},onBtnTap:function(b){Q(c,a(b.target))}})}),z()},c._fillValue=function(){c._hasValue=!0,w(!0,!0,0,!0)},c._clearValue=function(){a(".mbsc-sc-whl-multi .mbsc-sc-itm-sel",C).removeClass(o).removeAttr("aria-selected")},c._readValue=function(){var g=F.val()||"",e=0;""!==g&&(c._hasValue=!0),c._tempWheelArray=f=c._hasValue&&c._wheelArray?c._wheelArray.slice(0):d.parseValue.call(q,g,c)||[],c._tempSelected=b(!0,{},c._selected),a.each(d.wheels,function(c,b){a.each(b,function(b,a){h[e]=G(a,e),e++})}),w(!1,!1,0,!0),j("onRead")},c.__processSettings=function(a){d=c.settings,j=c.trigger,u=d.multiline,o="mbsc-sc-itm-sel mbsc-ic mbsc-ic-"+d.checkIcon,A=!d.touchUi,A&&(d.tapSelect=!0,d.circular=!1,d.rows=a.rows||K.rows||7)},c.__init=function(a){a&&(c._wheelArray=null),h=[],D={},t=d.showScrollArrows,g=d.scroll3d&&aP&&!t&&!A&&("ios"==d.theme||"ios"==d.baseTheme),e=d.height,y=g?2*Math.round((e-.03*(e*d.rows/2+3))/2):e,k=Math.round(1.8*d.rows),E=360/(2*k),t&&(d.rows=Math.max(3,d.rows))},c._getItemValue=x,c._tempSelected={},c._selected={},W||c.init()},H.prototype={_hasDef:!0,_hasTheme:!0,_hasLang:!0,_responsive:!0,_class:"scroller",_presets:N,_defaults:b({},I.prototype._defaults,{minWidth:80,height:40,rows:3,multiline:1,delay:200,readonly:!1,showLabel:!0,setOnTap:!1,wheels:[],preset:"",speedUnit:.0012,timeUnit:.08,checkIcon:"checkmark",compClass:"mbsc-sc",validate:function(){},formatValue:function(a){return a.join(" ")},parseValue:function(c,b){var d,e,f=[],g=[],h=0;return null!==c&&void 0!==c&&(f=(c+"").split(" ")),a.each(b.settings.wheels,function(i,c){a.each(c,function(i,c){e=c.data,d=b._getItemValue(e[0]),a.each(e,function(c,a){if(f[h]==b._getItemValue(a))return d=b._getItemValue(a),!1}),g.push(d),h++})}),g}})},O.Scroller=H,aR={inputClass:"",rtl:!1,showInput:!0,groupLabel:"Groups",dataHtml:"html",dataText:"text",dataValue:"value",dataGroup:"group",dataDisabled:"disabled",filterPlaceholderText:"Type to filter",filterEmptyText:"No results",filterClearIcon:"material-close"},N.select=function(e,ah){function M(h){var e,p,f,b,n,c,l=0,j=0,m={},o;(i={},s={},k=[],D=[],z.length=0,K)?a.each(A,function(g,a){n=a[d.dataText]+"",p=a[d.dataHtml],c=a[d.dataValue],f=a[d.dataGroup],b={value:c,html:p,text:n,index:g,cssClass:O?"mbsc-sel-gr-itm":""},i[c]=b,h&&!P(n,h)||(k.push(b),y&&(void 0===m[f]?(e={text:f,value:j,options:[],index:j},s[j]=e,m[f]=j,D.push(e),j++):e=s[m[f]],w&&(b.index=e.options.length),b.group=m[f],e.options.push(b)),a[d.dataDisabled]&&z.push(c))}):y?(o=!0,a("optgroup",g).each(function(c){s[c]={text:this.label,value:c,options:[],index:c},o=!0,a("option",this).each(function(a){b={value:this.value,text:this.text,index:w?a:l++,group:c,cssClass:O?"mbsc-sel-gr-itm":""},i[this.value]=b,h&&!P(this.text,h)||(o&&(D.push(s[c]),o=!1),k.push(b),s[c].options.push(b),this.disabled&&z.push(this.value))})})):a("option",g).each(function(a){b={value:this.value,text:this.text,index:a},i[this.value]=b,h&&!P(this.text,h)||(k.push(b),this.disabled&&z.push(this.value))}),ab=d.defaultValue?d.defaultValue:k.length?k[0].value:"",O&&(k=[],l=0,a.each(s,function(e,d){d.options.length&&(c="__group"+e,b={text:d.text,value:c,group:e,index:l++,cssClass:"mbsc-sel-gr"},i[c]=b,k.push(b),z.push(b.value),a.each(d.options,function(b,a){a.index=l++,k.push(a)}))})),G&&(k.length?G.removeClass("mbsc-sel-empty-v"):G.addClass("mbsc-sel-empty-v"))}function ad(b,d,f,c,g){var a,e=[];for(a=0;a<b.length;a++)e.push({value:b[a].value,display:b[a].html||b[a].text,cssClass:b[a].cssClass});return{circular:!1,multiple:d&&!c?1:c,cssClass:(d&&!c?"mbsc-sel-one":"")+" "+g,data:e,label:f}}function af(){return ad(D,m,d.groupLabel,!1,"mbsc-sel-gr-whl")}function _(){return ad(w&&s[p]?s[p].options:k,m,ag,q,"")}function Z(){var a=[[]];return t&&(E=af(),Y?a[0][h]=E:a[h]=[E]),J=_(),Y?a[0][f]=J:a[f]=[J],a}function H(b){q&&(b&&o(b)&&(b=b.split(",")),a.isArray(b)&&(b=b[0])),j=void 0===b||null===b||""===b?ab:b,!i[j]&&k&&k.length&&(j=k[0].value),t&&(p=i[j]?i[j].group:null)}function N(a){return W[a]||(i[a]?i[a].text:"")}function ak(e,a,g){var b,c,d=[],h=g?a._selected:a._tempSelected;if(m){for(b in h[f])d.push(N(b));return d.join(", ")}return c=e[f],N(c)}function L(){var b,c="",a=e.getVal(),f=d.formatValue.call(I,e.getArrayVal(),e,!0);if(d.filter&&"inline"==d.display||n.val(f),g.is("select")&&K){if(q)for(b=0;b<a.length;b++)c+='<option value="'+a[b]+'">'+N(a[b])+"</option>";else c='<option value="'+(null===a?"":a)+'">'+f+"</option>";g.html(c)}I!==n[0]&&g.val(a)}function Q(){var a={};a[f]=_(),C=!0,e.changeWheel(a)}function P(b,a){return a=a.replace(/[-\/\\^$*+?.()|[\]{}]/g,"\\$&"),b.match(new RegExp(a,"ig"))}function R(a){return d.data.dataField?a[d.data.dataField]:d.data.processResponse?d.data.processResponse(a):a}function T(b){var a={};M(b),H(j),d.wheels=Z(),a[f]=J,e._tempWheelArray[f]=j,t&&(a[h]=E,e._tempWheelArray[h]=p),e.changeWheel(a,0,!0),L()}function U(a){return e.trigger("onFilter",{filterText:a})}function $(a){a[h]!=p&&(p=a[h],j=s[p].options[0].value,a[f]=j,w?Q():e.setArrayVal(a,!1,!1,!0,V))}var n,A,ae,u,p,D,E,s,h,v,j,k,J,f,C,ab="",W={},V=1e3,I=this,g=a(I),aj=b({},e.settings),d=b(e.settings,aR,aj),G=a('<div class="mbsc-sel-empty">'+d.filterEmptyText+"</div>"),ai=d.readonly,i={},X=d.layout||(/top|bottom|inline/.test(d.display)||d.filter?"liquid":""),Y="liquid"==X||!d.touchUi,q=l(d.select)?d.select:"multiple"==d.select||g.prop("multiple"),m=q||!(!d.filter&&!d.tapSelect)&&1,F=this.id+"_dummy",aa=a('label[for="'+this.id+'"]').attr("for",F),ag=void 0!==d.label?d.label:aa.length?aa.text():g.attr("name"),x=d.group,K=!!d.data,y=K?!!d.group:a("optgroup",g).length,t=y&&x&&x.groupWheel!==!1,w=y&&x&&t&&x.clustered===!0,O=y&&(!x||x.header!==!1&&!w),B=g.val()||(q?[]:[""]),z=[];return e.setVal=function(a,g,j,b,k){if(m&&(null===a||void 0===a||q||(a=[a]),a&&o(a)&&(a=a.split(",")),e._tempSelected[f]=r(a),b||(e._selected[f]=r(a)),a=a?a[0]:null,t)){var c=i[a],d=c&&c.group;e._tempSelected[h]=r([d]),b||(e._selected[h]=r([d]))}e._setVal(a,g,j,b,k)},e.getVal=function(b,c){var a;return m?(a=S(b?e._tempSelected[f]:e._selected[f]),a=q?a:a.length?a[0]:null):(a=b?e._tempWheelArray:e._hasValue?e._wheelArray:null,a=a?a[f]:null),q?a:void 0!==a?y&&c?[i[a]?i[a].group:null,a]:a:null},e.refresh=function(b,f,e){e=e||c,b?(A=b,v||(d.data=b)):a.isArray(d.data)&&(A=d.data),!b&&v&&void 0===f?ac(d.data.url,function(a){A=R(a),T(),e()},d.data.dataType):(T(f),e())},ah.invalid||(d.invalid=z),t?(h=0,f=1):(h=-1,f=0),m&&(q&&g.prop("multiple",!0),B&&o(B)&&(B=B.split(",")),e._selected[f]=r(B)),e._$input&&e._$input.remove(),g.next().is(".mbsc-select-input")?n=g.next().removeAttr("tabindex"):d.input?n=a(d.input):(d.filter&&"inline"==d.display?e._$input=a('<div class="mbsc-sel-input-wrap"><input type="text" id="'+F+'" class="mbsc-select-input mbsc-control '+d.inputClass+'" readonly /></div>'):(n=a('<input type="text" id="'+F+'" class="mbsc-select-input mbsc-control '+d.inputClass+'" readonly />'),e._$input=n),d.showInput&&(e._$input.insertAfter(g),n||(n=e._$input.find("#"+F)))),e.attachShow(n.attr("placeholder",d.placeholder||"")),n[0]!==I&&g.addClass("mbsc-sel-hdn").attr("tabindex",-1).attr("data-enhance",!1),!m||d.rows%2||(d.rows=d.rows-1),d.filter&&(ae=d.filter.minLength||0),v=d.data&&d.data.url,v?e.refresh():(K&&(A=d.data),M(),H(g.val())),{layout:X,headerText:!1,anchor:n,compClass:"mbsc-sc mbsc-sel"+(m?" mbsc-sel-multi":""),setOnTap:!t||[!1,!0],formatValue:ak,tapSelect:m,parseValue:function(a){return H(void 0===a?g.val():a),t?[p,j]:[j]},validate:function(b){var c=b.index,a=[];return a[f]=d.invalid,w&&!C&&void 0===c&&Q(),C=!1,{disabled:a}},onRead:L,onFill:L,onMarkupReady:function(i,e){if(d.filter){var f,h,b,g=a(".mbsc-fr-w",i.target),c=a('<span class="mbsc-sel-filter-clear mbsc-ic mbsc-ic-'+d.filterClearIcon+'"></span>');"inline"==d.display?(f=n,n.parent().find(".mbsc-sel-filter-clear").remove()):(g.find(".mbsc-fr-c").before('<div class="mbsc-input mbsc-sel-filter-cont mbsc-control-w mbsc-'+d.theme+(d.baseTheme?" mbsc-"+d.baseTheme:"")+'"><span class="mbsc-input-wrap"><input tabindex="0" type="text" class="mbsc-sel-filter-input mbsc-control"/></span></div>'),f=g.find(".mbsc-sel-filter-input")),u=null,b=f[0],f.prop("readonly",!1).attr("placeholder",d.filterPlaceholderText).parent().append(c),g.find(".mbsc-fr-c").prepend(G),e._activeElm=b,e.tap(c,function(){u=null,b.value="",e.refresh(),c.removeClass("mbsc-sel-filter-show-clear"),U("")}),f.on("keydown",function(a){13!=a.keyCode&&27!=a.keyCode||(a.stopPropagation(),b.blur())}).on("input",function(){clearTimeout(h),b.value.length?c.addClass("mbsc-sel-filter-show-clear"):c.removeClass("mbsc-sel-filter-show-clear"),h=setTimeout(function(){u!==b.value&&U(b.value)!==!1&&(u=b.value,(u.length>=ae||!u.length)&&(v&&d.data.remoteFilter?ac(d.data.url+encodeURIComponent(u),function(a){e.refresh(R(a))},d.data.dataType):e.refresh(void 0,u)))},500)})}},onBeforeShow:function(){q&&d.counter&&(d.headerText=function(){var b=0;return a.each(e._tempSelected[f],function(){b++}),(b>1?d.selectedPluralText||d.selectedText:d.selectedText).replace(/{count}/,b)}),H(g.val()),m&&t&&(e._selected[h]=r([p])),d.filter&&M(void 0),e.settings.wheels=Z(),C=!0},onWheelGestureStart:function(a){a.index==h&&(d.readonly=[!1,!0])},onWheelAnimationEnd:function(b){var a=e.getArrayVal(!0);b.index==h?(d.readonly=ai,m||$(a)):b.index==f&&a[f]!=j&&(j=a[f],t&&i[j]&&i[j].group!=p&&(p=i[j].group,a[h]=p,e._tempSelected[h]=r([p]),e.setArrayVal(a,!1,!1,!0,V)))},onItemTap:function(a){var b;if(a.index==f&&(W[a.value]=i[a.value].text,m&&!q&&a.selected))return!1;if(a.index==h&&m){if(a.selected)return!1;b=e.getArrayVal(!0),b[h]=a.value,$(b)}},onClose:function(){v&&d.data.remoteFilter&&u&&e.refresh()},onDestroy:function(){e._$input&&e._$input.remove(),g.removeClass("mbsc-sel-hdn").removeAttr("tabindex")}}},aQ("select",H),al=/^(\d{4}|[+\-]\d{6})(?:-(\d{2})(?:-(\d{2}))?)?(?:T(\d{2}):(\d{2})(?::(\d{2})(?:\.(\d{3}))?)?((Z)|([+\-])(\d{2})(?::(\d{2}))?)?)?$/,ak=/^((\d{2}):(\d{2})(?::(\d{2})(?:\.(\d{3}))?)?(?:(Z)|([+\-])(\d{2})(?::(\d{2}))?)?)?$/,U=/^\d{1,2}(\/\d{1,2})?$/,z=/^w\d$/i,_={shortYearCutoff:"+10",monthNames:["January","February","March","April","May","June","July","August","September","October","November","December"],monthNamesShort:["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],dayNames:["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],dayNamesShort:["Sun","Mon","Tue","Wed","Thu","Fri","Sat"],dayNamesMin:["S","M","T","W","T","F","S"],amText:"am",pmText:"pm",getYear:function(a){return a.getFullYear()},getMonth:function(a){return a.getMonth()},getDay:function(a){return a.getDate()},getDate:v,getMaxDayOfMonth:function(a,b){return 32-new Date(a,b,32,12).getDate()},getWeekNumber:function(a){a=new Date(a),a.setHours(0,0,0),a.setDate(a.getDate()+4-(a.getDay()||7));var b=new Date(a.getFullYear(),0,1);return Math.ceil(((a-b)/864e5+1)/7)}},J.datetime={formatDate:w,parseDate:R},aA={separator:" ",dateFormat:"mm/dd/yy",dateDisplay:"MMddyy",timeFormat:"h:ii A",dayText:"Day",monthText:"Month",yearText:"Year",hourText:"Hours",minuteText:"Minutes",ampmText:"&nbsp;",secText:"Seconds",nowText:"Now",todayText:"Today"},ax=function(e){function W(d){var e,a,b,h,g=[];if(d){for(e=0;e<d.length;e++)if(a=d[e],a.start&&a.end&&!ak.test(a.start))for(b=new Date(f(a.start,m,c)),h=new Date(f(a.end,m,c));b<=h;)g.push(v(b.getFullYear(),b.getMonth(),b.getDate())),b.setDate(b.getDate()+1);else g.push(a);return g}return d}function l(b,a,c,d){return Math.min(d,Math.floor(b/a)*a+c)}function Q(a,b,c){return Math.floor((c-b)/a)*a+b}function aD(a){return c.getYear(a)}function aC(a){return c.getMonth(a)}function ax(a){return c.getDay(a)}function aw(b){var a=b.getHours();return a=u&&a>=12?a-12:a,l(a,y,F,ag)}function av(a){return l(a.getMinutes(),r,E,af)}function aG(a){return l(a.getSeconds(),A,O,ae)}function au(a){return a.getMilliseconds()}function at(a){return a.getHours()>11?1:0}function J(a){return a.getFullYear()+"-"+h(a.getMonth()+1)+"-"+h(a.getDate())}function ar(a){return l(Math.round((a.getTime()-new Date(a).setHours(0,0,0,0))/1e3),I||1,0,86400)}function p(e,a,c,f){var b;return void 0===d[a]||(b=+e[d[a]],isNaN(b))?c?k[a](c):void 0!==H[a]?H[a]:k[a](f):b}function t(b){var a,e=new Date((new Date).setHours(0,0,0,0)),f,g,i,h;return null===b?b:(void 0!==d.dd&&(a=b[d.dd].split("-"),a=new Date(a[0],a[1]-1,a[2])),void 0!==d.tt&&(a=a||e,a=new Date(a.getTime()+b[d.tt]%86400*1e3)),f=p(b,"y",a,e),g=p(b,"m",a,e),i=Math.min(p(b,"d",a,e),c.getMaxDayOfMonth(f,g)),h=p(b,"h",a,e),c.getDate(f,g,i,u&&p(b,"a",a,e)?h+12:h,p(b,"i",a,e),p(b,"s",a,e),p(b,"u",a,e)))}function C(b,g){var c,a,e=["y","m","d","a","h","i","s","u","dd","tt"],f=[];if(null===b||void 0===b)return b;for(c=0;c<e.length;c++)a=e[c],void 0!==d[a]&&(f[d[a]]=k[a](b)),g&&(H[a]=k[a](b));return f}function N(a,b){return b?Math.floor(new Date(a)/864e5):a.getMonth()+12*(a.getFullYear()-1970)}function aq(a){return{value:a,display:(/yy/i.test(D)?a:(a+"").substr(2,2))+(c.yearSuffix||"")}}function ap(a){return a}function am(b){var a=/d/i.test(b);return{label:"",cssClass:"mbsc-dt-whl-date",min:g?N(J(g),a):void 0,max:j?N(J(j),a):void 0,data:function(e){var f=new Date((new Date).setHours(0,0,0,0)),d=a?new Date(864e5*e):new Date(1970,e,1);return a&&(d=new Date(d.getUTCFullYear(),d.getUTCMonth(),d.getUTCDate())),{invalid:a&&!x(d,!0),value:J(d),display:f.getTime()==d.getTime()?c.todayText:w(b,d,c)}},getIndex:function(b){return N(b,a)}}}function al(d){var a,b,e,f=[];for(/s/i.test(d)?b=A:/i/i.test(d)?b=60*r:/h/i.test(d)&&(b=3600*y),I=n.tt=b,a=0;a<86400;a+=b)e=new Date((new Date).setHours(0,0,0,0)+1e3*a),f.push({value:a,display:w(d,e,c)});return{label:"",cssClass:"mbsc-dt-whl-time",data:f}}function ao(){var p,q,b,l,a,k,f,o,e=0,s=[],n=[],m=[];if(/date/i.test(B)){for(p=aa.split(/\|/.test(aa)?"|":""),l=0;l<p.length;l++)if(b=p[l],k=0,b.length)if(/y/i.test(b)&&(i.y=1,k++),/m/i.test(b)&&(i.y=1,i.m=1,k++),/d/i.test(b)&&(i.y=1,i.m=1,i.d=1,k++),k>1&&void 0===d.dd)d.dd=e,e++,n.push(am(b)),m=n,Z=!0;else if(/y/i.test(b)&&void 0===d.y)d.y=e,e++,n.push({cssClass:"mbsc-dt-whl-y",label:c.yearText,min:g?c.getYear(g):void 0,max:j?c.getYear(j):void 0,data:aq,getIndex:ap});else if(/m/i.test(b)&&void 0===d.m){for(d.m=e,f=[],e++,a=0;a<12;a++)o=D.replace(/[dy|]/gi,"").replace(/mm/,h(a+1)+(c.monthSuffix||"")).replace(/m/,a+1+(c.monthSuffix||"")),f.push({value:a,display:/MM/.test(o)?o.replace(/MM/,'<span class="mbsc-dt-month">'+c.monthNames[a]+"</span>"):o.replace(/M/,'<span class="mbsc-dt-month">'+c.monthNamesShort[a]+"</span>")});n.push({cssClass:"mbsc-dt-whl-m",label:c.monthText,data:f})}else if(/d/i.test(b)&&void 0===d.d){for(d.d=e,f=[],e++,a=1;a<32;a++)f.push({value:a,display:(/dd/i.test(D)?h(a):a)+(c.daySuffix||"")});n.push({cssClass:"mbsc-dt-whl-d",label:c.dayText,data:f})}s.push(n)}if(/time/i.test(B)){for(q=T.split(/\|/.test(T)?"|":""),l=0;l<q.length;l++)if(b=q[l],k=0,b.length&&(/h/i.test(b)&&(i.h=1,k++),/i/i.test(b)&&(i.i=1,k++),/s/i.test(b)&&(i.s=1,k++),/a/i.test(b)&&k++),k>1&&void 0===d.tt)d.tt=e,e++,m.push(al(b));else if(/h/i.test(b)&&void 0===d.h){for(f=[],d.h=e,i.h=1,e++,a=F;a<(u?12:24);a+=y)f.push({value:a,display:u&&0===a?12:/hh/i.test(G)?h(a):a});m.push({cssClass:"mbsc-dt-whl-h",label:c.hourText,data:f})}else if(/i/i.test(b)&&void 0===d.i){for(f=[],d.i=e,i.i=1,e++,a=E;a<60;a+=r)f.push({value:a,display:/ii/i.test(G)?h(a):a});m.push({cssClass:"mbsc-dt-whl-i",label:c.minuteText,data:f})}else if(/s/i.test(b)&&void 0===d.s){for(f=[],d.s=e,i.s=1,e++,a=O;a<60;a+=A)f.push({value:a,display:/ss/i.test(G)?h(a):a});m.push({cssClass:"mbsc-dt-whl-s",label:c.secText,data:f})}else/a/i.test(b)&&void 0===d.a&&(d.a=e,e++,m.push({cssClass:"mbsc-dt-whl-a",label:c.ampmText,data:/A/.test(b)?[{value:0,display:c.amText.toUpperCase()},{value:1,display:c.pmText.toUpperCase()}]:[{value:0,display:c.amText},{value:1,display:c.pmText}]}));m!=n&&s.push(m)}return s}function ay(c){var a,d,e,b={};if(c.is("input")){switch(c.attr("type")){case"date":a="yy-mm-dd";break;case"datetime":a="yy-mm-ddTHH:ii:ssZ";break;case"datetime-local":a="yy-mm-ddTHH:ii:ss";break;case"month":a="yy-mm",b.dateOrder="mmyy";break;case"time":a="HH:ii:ss"}b.format=a,d=c.attr("min"),e=c.attr("max"),d&&"undefined"!=d&&(b.min=R(a,d)),e&&"undefined"!=e&&(b.max=R(a,e))}return b}function az(a,e){var b,c,f=!1,d=!1,h=0,i=0,k=g?t(C(g)):-(1/0),l=j?t(C(j)):1/0;if(x(a))return a;if(a<k&&(a=k),a>l&&(a=l),b=a,c=a,2!==e)for(f=x(b,!0);!f&&b<l&&h<100;)b=new Date(b.getTime()+864e5),f=x(b,!0),h++;if(1!==e)for(d=x(c,!0);!d&&c>k&&i<100;)c=new Date(c.getTime()-864e5),d=x(c,!0),i++;return 1===e&&f?b:2===e&&d?c:ad(a,b)?b:ad(a,c)?c:i<=h&&d?c:b}function ad(a,b){return c.getYear(a)===c.getYear(b)&&c.getMonth(a)===c.getMonth(b)}function x(a,b){return!(!b&&a<g)&&!(!b&&a>j)&&(!!ab(a,P)||!ab(a,S))}function ab(d,g){var b,e,a;if(g)for(e=0;e<g.length;e++)if(b=g[e],a=b+"",!b.start)if(z.test(a)){if(a=+a.replace("w",""),a==d.getDay())return!0}else if(U.test(a)){if(a=a.split("/"),a[1]){if(a[0]-1==d.getMonth()&&a[1]==d.getDate())return!0}else if(a[0]==d.getDate())return!0}else if(b=f(b,m,c),d.getFullYear()==b.getFullYear()&&d.getMonth()==b.getMonth()&&d.getDate()==b.getDate())return!0;return!1}function ac(i,k,j,l,n,h,e){var b,g,d,a;if(i)for(g=0;g<i.length;g++)if(b=i[g],a=b+"",!b.start)if(z.test(a))for(a=+a.replace("w",""),d=a-l;d<n;d+=7)d>=0&&(h[d+1]=e);else U.test(a)?(a=a.split("/"),a[1]?a[0]-1==j&&(h[a[1]]=e):h[a[0]]=e):(b=f(b,m,c),c.getYear(b)==k&&c.getMonth(b)==j&&(h[c.getDay(b)]=e))}function $(w,A,e,L,H,B,N,K){var E,C,j,G,J,y,h,D,x,a,f,b,d,k,t,F,v,m,p,r,M={},i=c.getDate(L,H,B),g=["a","h","i","s"];if(w){for(h=0;h<w.length;h++)f=w[h],f.start&&(f.apply=!1,j=f.d,v=j+"",m=v.split("/"),j&&(j.getTime&&L==c.getYear(j)&&H==c.getMonth(j)&&B==c.getDay(j)||!z.test(v)&&(m[1]&&B==m[1]&&H==m[0]-1||!m[1]&&B==m[0])||z.test(v)&&i.getDay()==+v.replace("w",""))&&(f.apply=!0,M[i]=!0));for(h=0;h<w.length;h++)if(f=w[h],E=0,F=0,D=s[e],x=o[e],k=!0,t=!0,C=!1,f.start&&(f.apply||!f.d&&!M[i])){for(b=f.start.split(":"),d=f.end.split(":"),a=0;a<3;a++)void 0===b[a]&&(b[a]=0),void 0===d[a]&&(d[a]=59),b[a]=+b[a],d[a]=+d[a];if("tt"==e)D=l(Math.round((new Date(i).setHours(b[0],b[1],b[2])-new Date(i).setHours(0,0,0,0))/1e3),I,0,86400),x=l(Math.round((new Date(i).setHours(d[0],d[1],d[2])-new Date(i).setHours(0,0,0,0))/1e3),I,0,86400);else{for(b.unshift(b[0]>11?1:0),d.unshift(d[0]>11?1:0),u&&(b[1]>=12&&(b[1]=b[1]-12),d[1]>=12&&(d[1]=d[1]-12)),a=0;a<A;a++)void 0!==q[a]&&(p=l(b[a],n[g[a]],s[g[a]],o[g[a]]),r=l(d[a],n[g[a]],s[g[a]],o[g[a]]),G=0,J=0,y=0,u&&1==a&&(G=b[0]?12:0,J=d[0]?12:0,y=q[0]?12:0),k||(p=0),t||(r=o[g[a]]),(k||t)&&p+G<q[a]+y&&q[a]+y<r+J&&(C=!0),q[a]!=p&&(k=!1),q[a]!=r&&(t=!1));if(!K)for(a=A+1;a<4;a++)b[a]>0&&(E=n[e]),d[a]<o[g[a]]&&(F=n[e]);C||(p=l(b[A],n[e],s[e],o[e])+E,r=l(d[A],n[e],s[e],o[e])-F,k&&(D=p),t&&(x=r))}if(k||t||C)for(a=D;a<=x;a+=n[e])N[a]=!K}}}var I,Z,Y,d={},H={},i={},q=[],X=ay(a(this)),ah=b({},e.settings),aE=an[ah.calendarSystem],c=b(e.settings,_,aE,aA,X,ah),B=c.preset,M="datetime"==B?c.dateFormat+c.separator+c.timeFormat:"time"==B?c.timeFormat:c.dateFormat,m=X.format||M,aa=c.dateWheels||c.dateFormat,T=c.timeWheels||c.timeFormat,D=c.dateWheels||c.dateDisplay,G=T,aB=c.baseTheme||c.theme,S=W(c.invalid),P=W(c.valid),g=f(c.min,m,c),j=f(c.max,m,c),V=/time/i.test(B),u=/h/.test(G),aj=/D/.test(D),K=c.steps||{},y=K.hour||c.stepHour||1,r=K.minute||c.stepMinute||1,A=K.second||c.stepSecond||1,L=K.zeroBased,F=L||!g?0:g.getHours()%y,E=L||!g?0:g.getMinutes()%r,O=L||!g?0:g.getSeconds()%A,ag=Q(y,F,u?11:23),af=Q(r,E,59),ae=Q(r,E,59),s={y:g?g.getFullYear():-(1/0),m:0,d:1,h:F,i:E,s:O,a:0,tt:0},o={y:j?j.getFullYear():1/0,m:11,d:31,h:ag,i:af,s:ae,a:1,tt:86400},n={y:1,m:1,d:1,h:y,i:r,s:A,a:1,tt:1},aF={bootstrap:46,ios:50,material:46,mobiscroll:46,windows:50},k={y:aD,m:aC,d:ax,h:aw,i:av,s:aG,u:au,a:at,dd:J,tt:ar};return e.getVal=function(a){return e._hasValue||a?ai(t(e.getArrayVal(a)),c,m):null},e.getDate=function(a){return e._hasValue||a?t(e.getArrayVal(a)):null},e.setDate=function(a,b,c,d,f){e.setArrayVal(C(a,!0),b,f,d,c)},Y=ao(),c.isoParts=i,e._format=M,e._order=d,e.handlers.now=function(){e.setDate(new Date,e.live,1e3,!0,!0)},e.buttons.now={text:c.nowText,icon:c.nowIcon,handler:"now"},{minWidth:Z&&V?aF[aB]:void 0,compClass:"mbsc-dt mbsc-sc",wheels:Y,headerText:!!c.headerText&&function(){return w(M,t(e.getArrayVal(!0)),c)},formatValue:function(a){return w(m,t(a),c)},parseValue:function(a){return a||(H={},e._hasValue=!1),C(f(a||c.defaultValue||new Date,m,c,i),!!a)},validate:function(y){var b,i,u,w,F=y.values,v=y.index,E=y.direction,p=c.wheels[0][d.d],l=az(t(F),E),B=C(l),m=[],A={},f=k.y(l),h=k.m(l),r=c.getMaxDayOfMonth(f,h),x=!0,z=!0;if(a.each(["dd","y","m","d","tt","a","h","i","s"],function(v,b){var p=s[b],q=o[b],e=k[b](l),u,t;if(m[d[b]]=[],x&&g&&(p=k[b](g)),z&&j&&(q=k[b](j)),e<p&&(e=p),e>q&&(e=q),"dd"!==b&&"tt"!==b&&(x&&(x=e==p),z&&(z=e==q)),void 0!==d[b]){if("y"!=b&&"dd"!=b)for(i=s[b];i<=o[b];i+=n[b])(i<p||i>q)&&m[d[b]].push(i);"d"==b&&(u=c.getDate(f,h,1).getDay(),t={},ac(S,f,h,u,r,t,1),ac(P,f,h,u,r,t,0),a.each(t,function(a,c){c&&m[d[b]].push(a)}))}}),V&&a.each(["a","h","i","s","tt"],function(g,b){var j=k[b](l),i=k.d(l),c={};void 0!==d[b]&&($(S,g,b,f,h,i,c,0),$(P,g,b,f,h,i,c,1),a.each(c,function(a,c){c&&m[d[b]].push(a)}),q[g]=e.getValidValue(d[b],j,E,c))}),p&&(p._length!==r||aj&&(void 0===v||v===d.y||v===d.m))){for(A[d.d]=p,p.data=[],b=1;b<=r;b++)w=c.getDate(f,h,b).getDay(),u=D.replace(/[my|]/gi,"").replace(/dd/,(b<10?"0"+b:b)+(c.daySuffix||"")).replace(/d/,b+(c.daySuffix||"")),p.data.push({value:b,display:/DD/.test(u)?u.replace(/DD/,'<span class="mbsc-dt-day">'+c.dayNames[w]+"</span>"):u.replace(/D/,'<span class="mbsc-dt-day">'+c.dayNamesShort[w]+"</span>")});e._tempWheelArray[d.d]=B[d.d],e.changeWheel(A)}return{disabled:m,valid:B}}}},aw={controls:["calendar"],firstDay:0,weekDays:"short",maxMonthWidth:170,breakPointMd:768,months:1,pageBuffer:1,weeks:6,highlight:!0,outerMonthChange:!0,quickNav:!0,yearChange:!0,tabs:"auto",todayClass:"mbsc-cal-today",btnCalPrevClass:"mbsc-ic mbsc-ic-arrow-left6",btnCalNextClass:"mbsc-ic mbsc-ic-arrow-right6",dateText:"Date",timeText:"Time",todayText:"Today",fromText:"Start",toText:"End",moreEventsText:"{count} more",prevMonthText:"Previous Month",nextMonthText:"Next Month",prevYearText:"Previous Year",nextYearText:"Next Year"},av=function(e){function bv(a){a.hasClass("mbsc-cal-h")&&(a.removeClass("mbsc-cal-h"),e._onSelectShow())}function F(a){a.hasClass("mbsc-cal-h")||a.addClass("mbsc-cal-h")}function bb(a){a.hasClass("mbsc-cal-h")?bv(a):F(a)}function bt(){var c,f,g;l={},O=[],P={},n=e.trigger,ab=a(bj),g=b({},e.settings),d=b(e.settings,aw,g),c=d.controls.join(","),s=d.firstDay,S=d.rtl,B=d.pageBuffer,W=d.weekCounter,h=d.weeks,p=6==h,o="vertical"==d.calendarScroll,bg="inline"==d.display?ab.is("div")?ab:ab.parent():e._$window,aS="full"==d.weekDays?"":"min"==d.weekDays?"Min":"Short",f=d.layout||("inline"==d.display||/top|bottom/.test(d.display)&&d.touchUi?"liquid":""),C="liquid"==f,al=C?null:d.calendarWidth,w=S&&!o?-1:1,aI="mbsc-disabled "+(d.disabledClass||""),am="mbsc-selected "+(d.selectedTabClass||""),H="mbsc-selected "+(d.selectedClass||""),ao=Math.max(1,Math.floor(((d.calendarHeight||0)/h-45)/18)),c.match(/calendar/)&&(l.calendar=1,m=!0),c.match(/date/)&&!m&&(l.date=1),c.match(/time/)&&(l.time=1),d.controls.forEach(function(a){l[a]&&O.push(a)}),E=d.quickNav&&m&&p,J=d.yearChange&&p,C&&m&&"center"==d.display&&(e._isFullScreen=!0),d.layout=f,d.preset=(l.date||m?"date":"")+(l.time?"time":"")}function bn(){au=J?d.monthNamesShort:d.monthNames,az=d.yearSuffix||"",at=(d.dateWheels||d.dateFormat).search(/m/i),aj=(d.dateWheels||d.dateFormat).search(/y/i),K=e._format,d.min&&(q=A(f(d.min,K,d)),ar=d.getYear(q),a$=d.getMonth(q),a_=d.getDate(12*Math.floor(ar/12),0,1)),d.max&&(u=A(f(d.max,K,d)),ap=d.getYear(u),aX=d.getMonth(u),aU=d.getDate(12*Math.floor(ap/12),0,1))}function aa(a,b,c){a[b]=a[b]||[],a[b].push(c)}function _(k,g,h){var i,c,j,b,l=d.getYear(g),m=d.getMonth(g),e={};return k&&a.each(k,function(k,a){if(i=a.d||a.start||a,c=i+"",a.start&&a.end)for(b=A(f(a.start,K,d)),j=A(f(a.end,K,d));b<=j;)aa(e,b,a),b=d.getDate(d.getYear(b),d.getMonth(b),d.getDay(b)+1);else if(z.test(c))for(b=X(g,!1,+c.replace("w",""));b<=h;)aa(e,b,a),b=d.getDate(d.getYear(b),d.getMonth(b),d.getDay(b)+7);else if(U.test(c))if(c=c.split("/"),c[1])for(b=d.getDate(l,c[0]-1,c[1]);b<=h;)aa(e,b,a),b=d.getDate(d.getYear(b)+1,d.getMonth(b),c[1]);else for(b=d.getDate(l,m,c[0]);b<=h;)aa(e,b,a),b=d.getDate(d.getYear(b),d.getMonth(b)+1,c[0]);else aa(e,A(f(i,K,d)),a)}),e}function bk(a){return!(a<q)&&!(a>u)&&(void 0===bi[a]||void 0!==ba[a])}function bp(c){var a,g,h,i,d=!!ai[c]&&ai[c],f=!!an[c]&&an[c],k=f&&f[0].background?f[0].background:d&&d[0].background,j="";if(f)for(a=0;a<f.length;a++)j+=(f[a].cssClass||"")+" ";if(d){for(h='<div class="mbsc-cal-marks">',a=0;a<d.length;a++)g=d[a],j+=(g.cssClass||"")+" ",h+='<div class="mbsc-cal-mark"'+(g.color?' style="background:'+g.color+';"':"")+"></div>";h+="</div>"}return i={marked:d,background:k,cssClass:j,markup:P[c]?P[c].join(""):aF?h:""},b(i,e._getDayProps(c,i))}function aO(a){return' style="'+(o?"transform: translateY("+100*a+"%)":"left:"+100*a*w+"%")+'"'}function bm(){i="auto"==d.months?Math.max(1,Math.min(3,Math.floor((al||aV(bg))/280))):+d.months,aE=i+2*B,t=0,o=o&&i<2,ae=void 0===d.showOuterDays?i<2&&!o:d.showOuterDays}function bf(a){return y(a,i-1)>u&&(a=y(u,1-i)),a<q&&(a=q),a}function ah(b,f,d){var c=b.color,e=b.text;return'<div data-id="'+b._id+'" data-index="'+f+'" class="mbsc-cal-txt" title="'+a("<div>"+e+"</div>").text()+'"'+(c?' style="background:'+c+(d?";color:"+aK(c):"")+';"':"")+">"+(d?e:"")+"</div>"}function aW(c){var b=X(y(c,-t-B),!1),a=X(y(c,-t+i+B-1),!1);a=d.getDate(d.getYear(a),d.getMonth(a),d.getDay(a)+7*h),e._onGenMonth(b,a),bi=_(d.invalid,b,a),ba=_(d.valid,b,a),ai=_(d.labels||d.events||d.marked,b,a),an=_(d.colors,b,a),aD=e._labels||ai||an,N=d.labels||e._labels,N&&!function(){P={};for(var e={},c=b,g=function(){var u,q,i,j,g,m,a,k,h,t,o,v,b,r,l,p,w,n;c.getDay()==s&&(e={});for(u=ao,q=aD[c]||[],i=q.length,j=[],g=void 0,m=void 0,a=0,k=0,h=0,t=void 0;a<u;)(g=null,q.forEach(function(b,c){e[a]==b&&(g=b,m=c)}),a==u-1&&(k<i-1||i&&h==i&&!g))?(o=i-k,v=(o>1?d.moreEventsPluralText||d.moreEventsText:d.moreEventsText).replace(/{count}/,o),o&&j.push('<div class="mbsc-cal-txt-more">'+v+"</div>"),g&&(e[a]=null,g._days.forEach(function(b){P[b][a]='<div class="mbsc-cal-txt-more">'+d.moreEventsText.replace(/{count}/,1)+"</div>"})),k++,a++):g?(m==h&&h++,$(c,f(g.end))&&(e[a]=null),j.push(ah(g,m)),a++,k++,g._days.push(c)):h<i?(b=q[h],r=b.start&&f(b.start),l=b.end&&f(b.end),p=c.getDay(),w=s-p>0?7:0,n=l&&!$(r,l),r&&!$(c,r)&&p!=s||(void 0===b._id&&(b._id=bw++),n&&(e[a]=b),b._days=[c],t=n?100*Math.min(aC(c,A(l))+1,7+s-p-w):100,j.push(n?'<div class="mbsc-cal-txt-w" style="width:'+t+'%">'+ah(b,h,!0)+"</div>"+ah(b,h):ah(b,h,!0)),a++,k++),h++):(j.push('<div class="mbsc-cal-txt-ph"></div>'),a++);P[c]=j,c=d.getDate(d.getYear(c),d.getMonth(c),d.getDay(c)+1)};c<a;)g()}()}function bq(a){var b=d.getYear(a),c=d.getMonth(a);k=a,x=a,aL(a),n("onMonthChange",{year:b,month:c}),n("onMonthLoading",{year:b,month:c}),n("onPageChange",{firstDay:a}),n("onPageLoading",{firstDay:a}),aW(a)}function aP(a){var b=d.getYear(a),c=d.getMonth(a);void 0===aq?bh(a,b,c):aG(a,aq,!0),aJ(x,g.focus),g.focus=!1}function bh(c,d,e){var b=g.$scroller;a(".mbsc-cal-slide",b).removeClass("mbsc-cal-slide-a"),a(".mbsc-cal-slide",b).slice(B,B+i).addClass("mbsc-cal-slide-a"),N&&a(".mbsc-cal-slide-a .mbsc-cal-txt",b).on("mouseenter",function(){var c=a(this).attr("data-id");a('.mbsc-cal-txt[data-id="'+c+'"]',b).addClass("mbsc-hover")}).on("mouseleave",function(){a(".mbsc-cal-txt.mbsc-hover",b).removeClass("mbsc-hover")}),n("onMonthLoaded",{year:d,month:e}),n("onPageLoaded",{firstDay:c})}function bx(){var a,b;return a='<div class="mbsc-cal-tabs-c"><div class="mbsc-cal-tabs" role="tablist">',O.forEach(function(c,e){b=d[("calendar"==c?"date":c)+"Text"],a+='<div role="tab" aria-controls="'+(bj.id+"-mbsc-pnl-"+e)+'" class="mbsc-cal-tab mbsc-fr-btn-e '+(e?"":am)+'" data-control="'+c+'"'+(d.tabLink?'><a href="#">'+b+"</a>":' tabindex="0">'+b)+"</div>"}),a+="</div></div>"}function bl(){var b,a,c,f,e,j,g="",l=S?d.btnCalNextClass:d.btnCalPrevClass,m=S?d.btnCalPrevClass:d.btnCalNextClass;for(e='<div class="mbsc-cal-btn-w"><div data-step="-1" role="button" tabindex="0" aria-label="'+d.prevMonthText+'" class="'+l+' mbsc-cal-prev mbsc-cal-prev-m mbsc-cal-btn mbsc-fr-btn mbsc-fr-btn-e"></div>',a=0;a<(h?i:1);a++)e+='<div role="button" class="mbsc-cal-month"></div>';if(e+='<div data-step="1" role="button" tabindex="0" aria-label="'+d.nextMonthText+'" class="'+m+' mbsc-cal-next mbsc-cal-next-m mbsc-cal-btn mbsc-fr-btn mbsc-fr-btn-e"></div></div>',J&&(g='<div class="mbsc-cal-btn-w"><div data-step="-12" role="button" tabindex="0" aria-label="'+d.prevYearText+'" class="'+l+' mbsc-cal-prev mbsc-cal-prev-y mbsc-cal-btn mbsc-fr-btn mbsc-fr-btn-e"></div><div role="button" class="mbsc-cal-year"></div><div data-step="12" role="button" tabindex="0" aria-label="'+d.nextYearText+'" class="'+m+' mbsc-cal-next mbsc-cal-next-y mbsc-cal-btn mbsc-fr-btn mbsc-fr-btn-e"></div></div>'),h&&(j=aG(k)),b='<div class="mbsc-w-p mbsc-cal-c"><div class="mbsc-cal '+(p?"":" mbsc-cal-week-view")+(i>1?" mbsc-cal-multi ":"")+(W?" mbsc-cal-weeks ":"")+(o?" mbsc-cal-vertical":"")+(aF?" mbsc-cal-has-marks":"")+(N?" mbsc-cal-has-labels":"")+(ae?"":" mbsc-cal-hide-diff ")+(d.calendarClass||"")+'"'+(C?"":' style="width:'+(al||280*i)+'px;"')+'><div class="mbsc-cal-hdr">'+(aj<at||i>1?g+e:e+g)+"</div>",h){for(b+='<div class="mbsc-cal-body"><div class="mbsc-cal-day-picker"><div class="mbsc-cal-days-c">',c=0;c<i;c++){for(b+='<div class="mbsc-cal-days">',a=0;a<7;a++)f=(a+s)%7,b+='<div class="mbsc-cal-week-day'+f+'" aria-label="'+d.dayNames[f]+'">'+d["dayNames"+aS][f]+"</div>";b+="</div>"}b+='</div><div class="mbsc-cal-scroll-c mbsc-cal-day-scroll-c '+(d.calendarClass||"")+'"'+(d.calendarHeight?' style="height:'+d.calendarHeight+'px"':"")+'><div class="mbsc-cal-scroll" style="width:'+100/i+'%">'+j+"</div></div>"}if(b+="</div>",E){for(b+='<div class="mbsc-cal-month-picker mbsc-cal-picker mbsc-cal-h"><div class="mbsc-cal-scroll-c '+(d.calendarClass||"")+'"><div class="mbsc-cal-scroll">',a=0;a<3;a++){for(b+='<div class="mbsc-cal-slide"'+aO(a-1)+'><div role="grid" class="mbsc-cal-table"><div class="mbsc-cal-row">',c=0;c<12;c++)c&&c%3===0&&(b+='</div><div class="mbsc-cal-row">'),b+='<div role="gridcell"'+(1==a?' tabindex="-1" aria-label="'+d.monthNames[c]+'" data-val="'+c+'"':"")+' class="mbsc-cal-cell'+(1==a?" mbsc-btn-e":"")+'"><div class="mbsc-cal-cell-i mbsc-cal-cell-txt">'+(1==a?d.monthNamesShort[c]:"&nbsp;")+"</div></div>";b+="</div></div></div>"}for(b+="</div></div></div>",b+='<div class="mbsc-cal-year-picker mbsc-cal-picker mbsc-cal-h"><div class="mbsc-cal-scroll-c '+(d.calendarClass||"")+'"><div class="mbsc-cal-scroll">',a=-1;a<2;a++)b+=be(aQ(k,a),a);b+="</div></div></div>"}return b+="</div></div></div>"}function be(e,f){var b,a=d.getYear(e),c='<div class="mbsc-cal-slide"'+aO(f)+'><div role="grid" class="mbsc-cal-table"><div class="mbsc-cal-row">';for(b=0;b<12;b++)b&&b%3===0&&(c+='</div><div class="mbsc-cal-row">'),c+='<div role="gridcell" tabindex="-1" aria-label="'+a+'" data-val="'+a+'" class="mbsc-cal-cell mbsc-btn-e '+(a<ar||a>ap?" mbsc-disabled ":"")+(a==d.getYear(k)?H:"")+'"><div class="mbsc-cal-cell-i mbsc-cal-cell-txt">'+a+az+"</div></div>",a++;return c+="</div></div></div>"}function bd(x,D){var c,p,m,j,k,t,i,o,a,q,r,w,n,y,v,f,g=1,C=d.getYear(x),u=d.getMonth(x),z=d.getDay(x),A=null!==d.defaultValue||e._hasValue?e.getDate(!0):null,B=d.getDate(C,u,z).getDay(),E=s-B>0?7:0,l='<div class="mbsc-cal-slide"'+aO(D)+'><div role="grid" class="mbsc-cal-table"><div class="mbsc-cal-row">';for(f=0;f<7*h;f++)v=f+s-E,c=d.getDate(C,u,v-B+z),m=c.getFullYear(),j=c.getMonth(),k=c.getDate(),t=d.getMonth(c),i=d.getDay(c),y=d.getMaxDayOfMonth(m,j),o=m+"-"+(j+1)+"-"+k,a=b({valid:bk(c),selected:A&&$(A,c)},bp(c)),q=a.valid,r=a.selected,p=a.cssClass,w=new Date(c).setHours(12,0,0,0)===(new Date).setHours(12,0,0,0),n=t!==u,av[o]=a,f&&f%7===0&&(l+='</div><div class="mbsc-cal-row">'),W&&f%7===0&&("month"==W&&n&&g>1?g=1==k?1:2:"year"==W&&(g=d.getWeekNumber(d.getDate(m,j,k+(7-s+1)%7))),l+='<div role="gridcell" class="mbsc-cal-cell mbsc-cal-week-nr">'+g+"</div>",g++),l+='<div role="gridcell" aria-label="'+(w?d.todayText+", ":"")+d.dayNames[c.getDay()]+", "+d.monthNames[t]+" "+i+" "+(a.ariaLabel?", "+a.ariaLabel:"")+'"'+(n&&!ae?' aria-hidden="true"':' data-full="'+o+'"')+(r?' aria-selected="true"':"")+(q?' tabindex="-1"':' aria-disabled="true"')+' class="mbsc-cal-cell mbsc-cal-day mbsc-cal-day'+v%7+" "+(d.dayClass||"")+" "+(r?H:"")+(w?" "+d.todayClass:"")+(p?" "+p:"")+(1==i?" mbsc-cal-day-first":"")+(i==y?" mbsc-cal-day-last":"")+(n?" mbsc-cal-day-diff":"")+(q?" mbsc-btn-e":" mbsc-disabled")+(a.marked?" mbsc-cal-day-marked":"")+(a.background?" mbsc-cal-day-colored":"")+'"><div class="mbsc-cal-cell-i mbsc-cal-day-i"><div class="mbsc-cal-day-date mbsc-cal-cell-txt"'+(a.background?' style="background:'+a.background+";color:"+aK(a.background)+'"':"")+">"+i+"</div>"+(a.markup||"")+"</div></div>";return l+="</div></div></div>"}function aG(a,k,i){var b,e=d.getYear(a),f=d.getMonth(a),j=g?g.pos:0,c="";if(av={},h)for(k||(n("onMonthLoading",{year:e,month:f}),n("onPageLoading",{firstDay:a})),aW(a),b=0;b<aE;b++)c+=bd(y(a,b-t-B),j*w+b-B);return aq=void 0,i&&g&&(g.$active=null,g.$scroller.html(c),bh(a,e,f)),c}function aJ(c,d){if(g){var b=g.$active;b&&b.length&&(b[0].blur(),b.hasClass("mbsc-disabled")?b.removeAttr("tabindex"):b.attr("tabindex","-1")),g.$active=a('.mbsc-cal-slide-a .mbsc-cal-day[data-full="'+aN(c)+'"]',g.$scroller).attr("tabindex","0"),d&&g.$active.length&&g.$active[0].focus()}}function bo(c){var b=g&&g.$scroller;d.highlight&&g&&(a(".mbsc-selected",b).removeClass(H).removeAttr("aria-selected"),(null!==d.defaultValue||e._hasValue)&&a('.mbsc-cal-day[data-full="'+aN(c)+'"]',b).addClass(H).attr("aria-selected","true"))}function aZ(c,b){a(".mbsc-selected",b).removeClass(H).removeAttr("aria-selected"),a('.mbsc-cal-cell[data-val="'+c+'"]',b).addClass(H).attr("aria-selected","true")}function M(a,f,j,l){var b,c;r&&(a<q&&(a=q),a>u&&(a=u),"calendar"!==r&&R&&!f||(e._isSetDate=!f,m&&h&&(c=X(bf(a),p),aA&&(a<y(k,-t)||a>=y(k,i-t))&&(b=p?d.getMonth(c)-d.getMonth(k)+12*(d.getYear(c)-d.getYear(k)):Math.floor(aC(k,c)/(7*h)),b&&(g.queue=[],g.focus=l&&j,ag(g,b,j))),b&&j||aJ(a,l),f||bo(a),p||aL(a,!0),x=a,aA=!0),e._onSetDate(a,b),e._isSetDate=!1))}function aL(e,l){var b,k,h,c=d.getYear(e),g=d.getMonth(e),f=c+az;if(E){if(aZ(g,D.$scroller),aZ(c,L.$scroller),ag(L,Math.floor(c/12)-Math.floor(d.getYear(L.first)/12),!0),a(".mbsc-cal-cell",D.$scroller).removeClass("mbsc-disabled"),c===ar)for(b=0;b<a$;b++)a('.mbsc-cal-cell[data-val="'+b+'"]',D.$scroller).addClass("mbsc-disabled");if(c===ap)for(b=aX+1;b<=12;b++)a('.mbsc-cal-cell[data-val="'+b+'"]',D.$scroller).addClass("mbsc-disabled")}for(l||(aB(a(".mbsc-cal-prev-m",j),y(e,-t)<=q),aB(a(".mbsc-cal-next-m",j),y(e,i-t)>u),aB(a(".mbsc-cal-prev-y",j),d.getDate(c-1,g+1,1)<=q),aB(a(".mbsc-cal-next-y",j),d.getDate(c+1,g,1)>u)),aH.attr("aria-label",c).html(f),b=0;b<i;b++)e=d.getDate(c,g-t+b,1),k=d.getYear(e),h=d.getMonth(e),f=k+az,Z.eq(b).attr("aria-label",d.monthNames[h]+(J?"":" "+c)).html((!J&&aj<at?f+" ":"")+au[h]+(!J&&aj>at?" "+f:""))}function aB(a,b){b?a.addClass(aI).attr("aria-disabled","true"):a.removeClass(aI).removeAttr("aria-disabled")}function aT(f,p){var k=e.getDate(!0),h=f[0],i=f.attr("data-full"),j=i?i.split("-"):[],g=v(j[0],j[1]-1,j[2]),l=v(g.getFullYear(),g.getMonth(),g.getDate(),k.getHours(),k.getMinutes(),k.getSeconds()),o=f.hasClass("mbsc-selected"),c=a(p.target),m=c[0],r,q;if(ae||!f.hasClass("mbsc-cal-day-diff")){if(N&&h.contains(m))for(;m!=h;){if(c.hasClass("mbsc-cal-txt")||c.hasClass("mbsc-cal-txt-more")){if(r=c.attr("data-index"),q=aD[g],n("onLabelTap",{date:l,domEvent:p,target:c[0],labels:q,label:q[r]})===!1)return;break}c=c.parent(),m=c[0]}n("onDayChange",b(av[i],{date:l,target:h,selected:o}))===!1||d.readonly||f.hasClass("mbsc-disabled")||e._selectDay(f,g,l,o)}}function br(a){F(Y),M(d.getDate(d.getYear(g.first),a.attr("data-val"),1),!0,!0)}function bs(a){F(V),M(d.getDate(a.attr("data-val"),d.getMonth(g.first),1),!0,!0)}function X(a,g,b){var c=d.getYear(a),e=d.getMonth(a),f=a.getDay(),h=s-f>0?7:0;return g?d.getDate(c,e,1):d.getDate(c,e,(void 0===b?s:b)-h-f+d.getDay(a))}function y(a,b){var c=d.getYear(a),e=d.getMonth(a),f=d.getDay(a);return p?d.getDate(c,e+b,1):d.getDate(c,e,f+b*h*7)}function aQ(a,b){var c=12*Math.floor(d.getYear(a)/12);return d.getDate(c+12*b,0,1)}function ag(a,b,c,d){b&&e._isVisible&&(a.queue.push(arguments),1==a.queue.length&&aR(a,b,c,d))}function aR(b,c,s,q){var d,f,n="",e=b.$scroller,h=b.buffer,k=b.offset,l=b.pages,r=b.total,j=b.first,o=b.genPage,i=b.getFirst,g=c>0?Math.min(c,h):Math.max(c,-h),p=b.pos*w+g-c+k,m=Math.abs(c)>h;b.callback&&(b.load(),b.callback(!0)),b.first=i(j,c),b.pos+=g*w,b.changing=!0,b.load=function(){if(m){for(d=0;d<l;d++)f=c+d-k,n+=o(i(j,f),p+f);c>0?(a(".mbsc-cal-slide",e).slice(-l).remove(),e.append(n)):c<0&&(a(".mbsc-cal-slide",e).slice(0,l).remove(),e.prepend(n))}},b.callback=function(s){var n=Math.abs(g),l="";for(d=0;d<n;d++)f=c+d-k-h+(c>0?r-n:0),l+=o(i(j,f),p+f);if(c>0?(e.append(l),a(".mbsc-cal-slide",e).slice(0,g).remove()):c<0&&(e.prepend(l),a(".mbsc-cal-slide",e).slice(g).remove()),m){for(l="",d=0;d<n;d++)f=c+d-k-h+(c>0?0:r-n),l+=o(i(j,f),p+f);c>0?(a(".mbsc-cal-slide",e).slice(0,g).remove(),e.prepend(l)):c<0&&(a(".mbsc-cal-slide",e).slice(g).remove(),e.append(l))}af(b),q&&!s&&q(),b.callback=null,b.load=null,b.queue.shift(),m=!1,b.queue.length?aR.apply(this,b.queue[0]):(b.changing=!1,b.onAfterChange(b.first))},b.onBeforeChange(b.first),b.load&&(b.load(),b.scroller.scroll(-b.pos*b.size,s?200:0,!1,b.callback))}function aM(h,r,p,g,k,i,j,s,l,m,n,f,q){var c=o?"Y":"X",b={$scroller:a(".mbsc-cal-scroll",h),queue:[],buffer:g,offset:k,pages:i,first:s,total:j,pos:0,min:r,max:p,genPage:f,getFirst:q,onBeforeChange:m,onAfterChange:n};return b.scroller=new T(h,{axis:c,easing:"",contSize:0,maxSnapScroll:g,mousewheel:void 0===d.mousewheel?o:d.mousewheel,time:200,lock:!0,rtl:S,stopProp:!1,minScroll:0,maxScroll:0,onBtnTap:function(b){"touchend"==b.domEvent.type&&G(),l(a(b.target),b.domEvent)},onAnimationStart:function(){b.changing=!0},onAnimationEnd:function(a){f&&ag(b,Math.round((-b.pos*b.size-a["pos"+c])/b.size)*w)}}),e._scrollers.push(b.scroller),b}function af(a,g){var c,d=0,e=0,f=a.first;if(!a.changing||!g){if(a.getFirst){for(d=a.buffer,e=a.buffer;e&&a.getFirst(f,e+a.pages-a.offset-1)>a.max;)e--;for(;d&&a.getFirst(f,1-d-a.offset)<=a.min;)d--}c=Math.round(ak/a.pages),C&&c&&a.size!=c&&a.$scroller[o?"height":"width"](c),b(a.scroller.settings,{snap:c,minScroll:(-a.pos*w-e)*c,maxScroll:(-a.pos*w+d)*c}),a.size=c,a.scroller.refresh()}}function aY(a){e._onRefresh(a),e._isVisible&&m&&h&&(g&&g.changing?aq=a:(aG(k,a,!0),aJ(x)))}function bu(){if(m&&h){var b=a(".mbsc-cal-scroll-c",j);g=aM(b[0],q,u,B,t,i,aE,k,aT,bq,aP,bd,y),E&&(D=aM(b[1],null,null,1,0,1,3,k,br),L=aM(b[2],a_,aU,1,0,1,3,k,bs,c,c,be,aQ),e.tap(Z,function(){bb(Y),F(V)}),e.tap(aH,function(){bb(V),F(Y)})),ay(a(".mbsc-cal-btn",j),function(c,a,d,b){ag(g,a,!0,b)}),aP(k),null===d.defaultValue&&!e._hasValue||e._multiple||(e._activeElm=g.$active[0]),I.on("keydown",function(f){var c,a=d.getYear(x),b=d.getMonth(x),e=d.getDay(x);switch(f.keyCode){case 32:aT(g.$active,f);break;case 37:c=d.getDate(a,b,e-1*w);break;case 39:c=d.getDate(a,b,e+1*w);break;case 38:c=d.getDate(a,b,e-7);break;case 40:c=d.getDate(a,b,e+7);break;case 36:c=d.getDate(a,b,1);break;case 35:c=d.getDate(a,b+1,0);break;case 33:c=f.altKey?d.getDate(a-1,b,e):p?d.getDate(a,b-1,e):d.getDate(a,b,e-7*h);break;case 34:c=f.altKey?d.getDate(a+1,b,e):p?d.getDate(a,b+1,e):d.getDate(a,b,e+7*h)}c&&(f.preventDefault(),M(c,!0,!1,!0))})}e.tap(a(".mbsc-cal-tab",j),function(){e.changeTab(a(this).attr("data-control"))})}var ab,I,j,Z,Y,ad,Q,aH,V,x,bc,h,ak,al,bg,l,O,aI,H,am,k,P,av,g,K,s,m,N,aF,R,E,bi,ac,C,p,S,o,aD,ai,an,u,aU,ao,aX,ap,q,a_,a$,ar,at,au,D,aA,aq,B,i,t,aE,w,d,ae,n,ba,r,W,aS,J,aj,L,az,bw=1,bj=this;return bt(),bc=ax.call(this,e),bn(),e.refresh=function(){aY(!1)},e.redraw=function(){aY(!0)},e.navigate=function(a,b){M(f(a,K,d),!0,b)},e.changeTab=function(b){e._isVisible&&l[b]&&r!=b&&(r=b,a(".mbsc-cal-tab",j).removeClass(am).removeAttr("aria-selected"),a('.mbsc-cal-tab[data-control="'+b+'"]',j).addClass(am).attr("aria-selected","true"),R&&(Q.addClass("mbsc-cal-h"),l[r].removeClass("mbsc-cal-h")),"calendar"==r&&M(e.getDate(!0),!1,!0),e._showDayPicker(),e.trigger("onTabChange",{tab:r}))},e._checkSize=!0,e._onGenMonth=c,e._onSelectShow=c,e._onSetDate=c,e._onRefresh=c,e._getDayProps=c,e._prepareObj=_,e._showDayPicker=function(){E&&(F(V,!0),F(Y,!0))},e._selectDay=e.__selectDay=function(c,f,b){var a=e.live;aA=d.outerMonthChange,ac=!0,e.setDate(b,a,1e3,!a,!0),a&&n("onSet",{valueText:e._value})},b(bc,{labels:null,compClass:"mbsc-calendar mbsc-dt mbsc-sc",onMarkupReady:function(c){var b=0;j=a(c.target),ad=a(".mbsc-fr-c",j),x=e.getDate(!0),ak=0,m&&(aF=!(!d.marked&&!d.data||d.labels||d.multiLabel||d.showEventCount),aA=!0,r="calendar",bm(),k=X(bf(x),p),ad.append(bl()),Z=a(".mbsc-cal-month",j),aH=a(".mbsc-cal-year",j),I=a(".mbsc-cal-day-scroll-c",j)),E&&(V=a(".mbsc-cal-year-picker",j),Y=a(".mbsc-cal-month-picker",j)),Q=a(".mbsc-w-p",j),O.length>1&&ad.before(bx()),["date","time","calendar"].forEach(function(a){l[a]?(l[a]=Q.eq(b),b++):"date"==a&&!l.date&&m&&(Q.eq(b).remove(),b++)}),O.forEach(function(a){ad.append(l[a])}),!m&&l.date&&l.date.css("position","relative"),e._scrollers=[],bu()},onShow:function(){m&&h&&aL(p?k:x)},onHide:function(){e._scrollers.forEach(function(a){a.destroy()}),av=null,g=null,D=null,L=null,r=null},onValidated:function(f){var b,d,a=f.index,c=e._order;d=e.getDate(!0),ac?b="calendar":void 0!==a&&(b=c.dd==a||c.d==a||c.m==a||c.y==a?"date":"time"),n("onSetDate",{date:d,control:b}),"time"!==b&&M(d,!1,!!f.time,ac&&!e._multiple),ac=!1},onPosition:function(b){var c,w,u,f,q,n,s,B=b.oldHeight,p=b.windowHeight,y,x,z,A,v;if(R=(b.hasTabs||d.tabs===!0||d.tabs!==!1&&C)&&O.length>1,C&&(b.windowWidth>=d.breakPointMd?a(b.target).addClass("mbsc-fr-md"):a(b.target).removeClass("mbsc-fr-md")),R?(j.addClass("mbsc-cal-tabbed"),r=a(".mbsc-cal-tab.mbsc-selected",j).attr("data-control"),Q.addClass("mbsc-cal-h"),l[r].removeClass("mbsc-cal-h")):(j.removeClass("mbsc-cal-tabbed"),Q.removeClass("mbsc-cal-h")),e._isFullScreen&&(I.height(""),q=b.popup.offsetHeight,s=p-q+I[0].offsetHeight,p>=q&&I.height(s)),N&&h&&p!=B&&(y=s||I[0].offsetHeight,x=a(".mbsc-cal-txt-ph")[0],z=x.offsetTop,A=x.offsetHeight,v=Math.max(1,Math.floor((y/h-z)/(A+2))),ao!=v&&(ao=v,e.redraw())),m&&h){if(n=C||o||R?I[0][o?"offsetHeight":"offsetWidth"]:al||280*i,f=n!=ak,ak=n,C&&f&&J)for(au=d.maxMonthWidth>Z[0].offsetWidth?d.monthNamesShort:d.monthNames,w=d.getYear(k),u=d.getMonth(k),c=0;c<i;c++)Z.eq(c).text(au[d.getMonth(d.getDate(w,u-t+c,1))]);f&&af(g,!0)}E&&f&&(af(D,!0),af(L,!0))}})},au={},N.calendar=function(e){function s(a){return v(a.getFullYear(),a.getMonth(),a.getDate())}function m(a){var b,e,g=null;if(d={},a&&a.length)for(e=0;e<a.length;e++)b=f(a[e],i,c,c.isoParts),g=g||b,d[s(b)]=b;return g}function n(){e.redraw()}var h,k,i,r,o,t=b({},e.settings),c=b(e.settings,au,t),p="mbsc-selected "+(c.selectedClass||""),j=c.defaultValue,g="multiple"==c.select||c.select>1||"week"==c.selectType,q=l(c.select)?c.select:1/0,d={};return h=av.call(this,e),r=void 0===c.firstSelectDay?c.firstDay:c.firstSelectDay,i=e._format,g&&m(j),e._multiple=g,e._getDayProps=function(a){return{selected:g?void 0!==d[a]:void 0}},e._selectDay=function(o,b,l,m){var h,i,f,j;if(c.setOnDayTap&&"multiple"!=c.select&&"inline"!=c.display)return e.setDate(l),void e.select();if(g)if("week"==c.selectType){f=b.getDay()-r;for(f=f<0?7+f:f,"multiple"!=c.select&&(d={}),h=0;h<7;h++)i=v(b.getFullYear(),b.getMonth(),b.getDate()-f+h),m?delete d[i]:S(d).length/7<q&&(d[i]=i);n()}else j=a('.mbsc-cal-day[data-full="'+o.attr("data-full")+'"]',k),m?(j.removeClass(p).removeAttr("aria-selected"),delete d[b]):S(d).length<q&&(j.addClass(p).attr("aria-selected","true"),d[b]=b);e.__selectDay(o,b,l)},e.setVal=function(a,b,c,d,f){g&&(a=m(a)),e._setVal(a,b,c,d,f),g&&n()},e.getVal=function(f){var a,b=[];if(g){for(a in d)b.push(ai(d[a],c,i));return b}return ai(e.getDate(f),c,i)},b({},h,{highlight:!g,outerMonthChange:!g,parseValue:function(a){return g&&a&&"string"==typeof a&&(a=m(a.split(","))),g&&j&&j.length&&(c.defaultValue=j[0]),h.parseValue.call(this,a)},formatValue:function(f){var a,b=[];if(g){for(a in d)b.push(w(i,d[a],c));return b.join(", ")}return h.formatValue.call(this,f,e)},onClear:function(){g&&(d={},n())},onBeforeShow:function(){void 0!==c.setOnDayTap||c.buttons&&c.buttons.length||1!=c.controls.length||(c.setOnDayTap=!0),c.setOnDayTap&&"inline"!=c.display&&(c.outerMonthChange=!1),c.counter&&g&&(c.headerText=function(){var b=0,e="week"==c.selectType?7:1;return a.each(d,function(){b++}),b=Math.round(b/e),(b>1?c.selectedPluralText||c.selectedText:c.selectedText).replace(/{count}/,b)})},onMarkupReady:function(c){h.onMarkupReady.call(this,c),k=a(c.target),g&&(a(".mbsc-fr-hdr",k).attr("aria-live","off"),o=b({},d))},onCancel:function(){!e.live&&g&&(d=b({},o))}})},aQ("calendar",H),d.customTheme("mobiscroll-dark","mobiscroll"),ap=d.themes,t="mobiscroll","android"==g?t="material":"ios"==g?t="ios":"wp"==g&&(t="windows"),a.each(ap.frame,function(a,b){return t&&b.baseTheme==t&&"mobiscroll-dark"!=a&&"material-dark"!=a&&"windows-dark"!=a&&"ios-dark"!=a?(d.autoTheme=a,!1):void(a==t&&(d.autoTheme=a))}),d}),!function(a,b){"object"==typeof exports&&"undefined"!=typeof module?module.exports=b(require("jquery")):"function"==typeof define&&define.amd?define(["jquery"],b):(a=a||self).mobiscroll=b(a.jQuery)}(this,function(S){"use strict";var b,ae,aV,bG,by,m,aF,x,bY,aE,q,dm,aj,bP,c_,cW,aB,E,bd,a$,at,K,a,cs,B,l,ct,cF,c,cU,bn,M,A,az,P,ag,y,Y,aL,bs,bR,n,aN,aP,bW,dl,aC,aA,aX,da,bJ,aS,cZ,bZ,cX,i,be,cR,cN,cL,cI,aI,aq,bp,cE,ab,bx,ar,bz,bA,bB,bC,bD,bE,bH,bI,ck,aU,bS,ce,w,cf,cg,ci,cj,aw,cl,D,U,cy,cB,J,am,W,cG,ao,bm,cJ,cK,bl,cM,bk,cO,bh,cQ,bg,cS,cT,cV,al,c$,bM,dc,dd,de,df,dq,dh,di,aR,aD,ai,ah,G,L,aa,aH,dn,R;function Z(a){return(Z="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(a){return typeof a}:function(a){return a&&"function"==typeof Symbol&&a.constructor===Symbol&&a!==Symbol.prototype?"symbol":typeof a})(a)}function F(a,b){if(!(a instanceof b))throw new TypeError("Cannot call a class as a function")}function dj(d,c){for(var b=0,a;b<c.length;b++)a=c[b],a.enumerable=a.enumerable||!1,a.configurable=!0,"value"in a&&(a.writable=!0),Object.defineProperty(d,a.key,a)}function _(a,b,c){return b&&dj(a.prototype,b),c&&dj(a,c),a}function $(b,a){if("function"!=typeof a&&null!==a)throw new TypeError("Super expression must either be null or a function");b.prototype=Object.create(a&&a.prototype,{constructor:{value:b,writable:!0,configurable:!0}}),a&&db(b,a)}function r(a){return(r=Object.setPrototypeOf?Object.getPrototypeOf:function(a){return a.__proto__||Object.getPrototypeOf(a)})(a)}function db(a,b){return(db=Object.setPrototypeOf||function(a,b){return a.__proto__=b,a})(a,b)}function an(a){if(void 0===a)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return a}function T(b,a){return!a||"object"!=typeof a&&"function"!=typeof a?an(b):a}function af(a,b,c){return(af="undefined"!=typeof Reflect&&Reflect.get?Reflect.get:function(d,b,e){var c=function(a,b){for(;!Object.prototype.hasOwnProperty.call(a,b)&&null!==(a=r(a)););return a}(d,b),a;if(c)return a=Object.getOwnPropertyDescriptor(c,b),a.get?a.get.call(e):a.value})(a,b,c||a)}S=S&&S.hasOwnProperty("default")?S.default:S,b=b||{},ae={},aV={},bG=S.extend,by={};function cv(c,a,e){var d=c;return"object"===Z(a)?c.each(function(){new a.component(this,a)}):("string"==typeof a&&c.each(function(){var f,c=b.instances[this.id];if(c&&c[a]&&void 0!==(f=c[a].apply(this,Array.prototype.slice.call(e,1))))return d=f,!1}),d)}function h(a,b,c){by[a]=function(d){return cv(this,bG(d,{component:b,preset:!1===c?void 0:a}),arguments)}}(b.$=S).mobiscroll=b,S.fn.mobiscroll=function(a){return bG(this,by),cv(this,a,arguments)},aE=[],q="undefined"!=typeof window,dm=q&&window.matchMedia&&window.matchMedia("(prefers-color-scheme:dark)").matches,aj=q?navigator.userAgent:"",bP=q?navigator.platform:"",c_=q?navigator.maxTouchPoints:0,cW=/Safari/.test(aj),aB=aj.match(/Android|iPhone|iPad|iPod|Windows Phone|Windows|MSIE/i),E=q&&window.requestAnimationFrame||function(a){return setTimeout(a,20)},bd=q&&window.cancelAnimationFrame||function(a){clearTimeout(a)};function d(){}function bb(a){var b,c=[];for(b in a)c.push(a[b]);return c}function ac(b){var a,c={};if(b)for(a=0;a<b.length;a++)c[b[a]]=b[a];return c}function s(a){return 0<=a-parseFloat(a)}function I(a){return"string"==typeof a}function p(a,b,c){return Math.max(b,Math.min(a,c))}function u(a,b){for(a+="",b=b||2;a.length<b;)a="0"+a;return a}function bo(c,a){var b,d;return a=a||100,function(){var f=this,e=+new Date,g=arguments;b&&e<b+a?(clearTimeout(d),d=setTimeout(function(){b=e,c.apply(f,g)},a)):(b=e,c.apply(f,g))}}function bq(a){"vibrate"in navigator&&navigator.vibrate(a||50)}function v(b,a,c){return 100*(b-a)/(c-a)}function br(b,c,d){var a=d.attr(b);return void 0===a||""===a?c:"true"===a}/Android/i.test(aB)?(m="android",(aF=aj.match(/Android\s+([\d\.]+)/i))&&(aE=aF[0].replace("Android ","").split("."))):/iPhone|iPad|iPod/i.test(aB)||/iPhone|iPad|iPod/i.test(bP)||"MacIntel"===bP&&1<c_?(m="ios",(aF=aj.match(/OS\s+([\d\_]+)/i))&&(aE=aF[0].replace(/_/g,".").replace("OS ","").split("."))):/Windows Phone/i.test(aB)?m="wp":/Windows|MSIE/i.test(aB)&&(m="windows"),x=aE[0],bY=aE[1],at=0;function Q(){at++,setTimeout(function(){at--},500)}function cn(d,a){if(!a.mbscClick){var b=(d.originalEvent||d).changedTouches[0],c=document.createEvent("MouseEvents");c.initMouseEvent("click",!0,!0,window,1,b.screenX,b.screenY,b.clientX,b.clientY,!1,!1,!1,!1,0,null),c.isMbscTap=!0,c.isIonicTap=!0,a$=!0,a.mbscChange=!0,a.mbscClick=!0,a.dispatchEvent(c),a$=!1,Q(),setTimeout(function(){delete a.mbscClick})}}function e(b,d,e){var a=b.originalEvent||b,c=(e?"page":"client")+d;return a.targetTouches&&a.targetTouches[0]?a.targetTouches[0][c]:a.changedTouches&&a.changedTouches[0]?a.changedTouches[0][c]:b[c]}function aY(a){var c=["switch","range","rating","segmented","stepper"],f=a[0],e=a.attr("data-role"),d=a.attr("type")||f.nodeName.toLowerCase(),b;if(/(switch|range|rating|segmented|stepper|select)/.test(e))d=e;else for(b=0;b<c.length;b++)a.is("[mbsc-"+c[b]+"]")&&(d=c[b]);return d}function bQ(a,b,c){a.focus(),/(button|submit|checkbox|switch|radio)/.test(b)&&c.preventDefault(),/select/.test(b)||cn(c,a)}function bV(f,l,m,j,d,n){var i,g,a,c,k,h=(0,b.$)(l);d=d||9,f.settings.tap&&h.on("touchstart.mbsc",function(b){a||(j&&b.preventDefault(),a=this,i=e(b,"X"),g=e(b,"Y"),c=!1,k=new Date)}).on("touchcancel.mbsc",function(){a=!1}).on("touchmove.mbsc",function(b){a&&!c&&(Math.abs(e(b,"X")-i)>d||Math.abs(e(b,"Y")-g)>d)&&(c=!0)}).on("touchend.mbsc",function(b){a&&(n&&new Date-k<100||!c?cn(b,b.target):Q(),a=!1)}),h.on("click.mbsc",function(a){j&&a.preventDefault(),m.call(this,a,f)})}function dv(a){if(at&&!a$&&!a.isMbscTap&&("TEXTAREA"!=a.target.nodeName||"mousedown"!=a.type))return a.stopPropagation(),a.preventDefault(),!1}function dp(a){return a[0].innerWidth||a.innerWidth()}function cp(b){var a=b.theme,c=b.themeVariant;return"auto"!=a&&a||(a=K.autoTheme),"default"==a&&(a="mobiscroll"),("dark"===c||dm&&"auto"===c)&&K.themes.form[a+"-dark"]&&(a+="-dark"),a}function o(b,c,d){q&&a(function(){a(b).each(function(){new c(this,{})}),a(document).on("mbsc-enhance",function(d,e){a(d.target).is(b)?new c(d.target,e||{}):a(b,d.target).each(function(){new c(this,e||{})})}),d&&a(document).on("mbsc-refresh",function(d){var c;a(d.target).is(b)?(c=B[d.target.id])&&c.refresh():a(b,d.target).each(function(){(c=B[this.id])&&c.refresh()})})})}q&&(["mouseover","mousedown","mouseup","click"].forEach(function(a){document.addEventListener(a,dv,!0)}),"android"==m&&x<5&&document.addEventListener("change",function(a){at&&"checkbox"==a.target.type&&!a.target.mbscChange&&(a.stopPropagation(),a.preventDefault()),delete a.target.mbscChange},!0)),a=b.$,cs=+new Date,B={},l={},ct={},cF={xsmall:0,small:576,medium:768,large:992,xlarge:1200},c=a.extend,c(ae,{getCoord:e,preventClick:Q,vibrate:bq}),K=c(b,{$:a,version:"4.9.1",autoTheme:"mobiscroll",themes:{form:{},page:{},frame:{},scroller:{},listview:{},navigation:{},progress:{},card:{}},platform:{name:m,majorVersion:x,minorVersion:bY},uid:"c5d09426",i18n:{},instances:B,classes:l,util:ae,settings:{},setDefaults:function(a){c(this.settings,a)},customTheme:function(g,e){var a,f=b.themes,d=["frame","scroller","listview","navigation","form","page","progress","card"];for(a=0;a<d.length;a++)f[d[a]][g]=c({},f[d[a]][e],{baseTheme:e})}});function C(g,f){var o,n,i,h,e,k,l,m,j,b=this;function p(d){var b,c=ct;return e.responsive&&(b=d||dp(o),a.each(e.responsive,function(d,a){b>=(a.breakpoint||cF[d])&&(c=a)})),c}b.settings={},b.element=g,b._init=d,b._destroy=d,b._processSettings=d,b._checkResp=function(c){if(b&&b._responsive){var a=p(c);if(h!==a)return h=a,b.init({}),!0}},b._getRespCont=function(){return a(e.context)[0]},b.init=function(a,d){var q,r;for(q in a&&b.getVal&&(r=b.getVal()),b.settings)delete b.settings[q];e=b.settings,c(f,a),b._hasDef&&(j=K.settings),c(e,b._defaults,j,f),b._hasTheme&&(l=cp(e),f.theme=l,k=K.themes[b._class]?K.themes[b._class][l]:{}),b._hasLang&&(n=K.i18n[e.lang]),c(e,k,n,j,f),o=b._getRespCont(),b._responsive&&(h=h||p(),c(e,h)),b._processSettings(h||{}),b._presets&&(i=b._presets[e.preset])&&(i=i.call(g,b,f),c(e,i,f,h)),b._init(a),a&&b.setVal&&b.setVal(void 0===d?r:d,!0),m("onInit")},b.destroy=function(){b&&(b._destroy(),m("onDestroy"),delete B[g.id],b=null)},b.tap=function(a,c,d,e,f){bV(b,a,c,d,e,f)},b.trigger=function(d,h){var e,a,c,l=[j,k,i,f];for(a=0;a<4;a++)(c=l[a])&&c[d]&&(e=c[d].call(g,h||{},b));return e},b.option=function(a,g,h){var c={},d=["data","invalid","valid","readonly"];/calendar|eventcalendar|range/.test(e.preset)&&d.push("marked","labels","colors"),"object"===Z(a)?c=a:c[a]=g,d.forEach(function(a){f[a]=e[a]}),b.init(c,h)},b.getInst=function(){return b},f=f||{},m=b.trigger,b.__ready||(a(g).addClass("mbsc-comp"),g.id?B[g.id]&&B[g.id].destroy():g.id="mobiscroll"+ ++cs,(B[g.id]=b).__ready=!0)}function O(c,h,d,b,e,f,g){var a=new Date(c,h,d,b||0,e||0,f||0,g||0);return 23==a.getHours()&&0===(b||0)&&a.setHours(a.getHours()+2),a}function j(g,b,m){var e,i,d,a,k,h;if(!b)return null;function j(b){for(var a=0;e+1<g.length&&g.charAt(e+1)==b;)a++,e++;return a}function f(b,c,d){var a=""+c;if(j(b))for(;a.length<d;)a="0"+a;return a}function l(b,a,c,d){return j(b)?d[a]:c[a]}d=c({},az,m),a="",k=!1;for(e=0;e<g.length;e++)if(k)"'"!=g.charAt(e)||j("'")?a+=g.charAt(e):k=!1;else switch(g.charAt(e)){case"d":a+=f("d",d.getDay(b),2);break;case"D":a+=l("D",b.getDay(),d.dayNamesShort,d.dayNames);break;case"o":a+=f("o",(b.getTime()-new Date(b.getFullYear(),0,0).getTime())/864e5,3);break;case"m":a+=f("m",d.getMonth(b)+1,2);break;case"M":a+=l("M",d.getMonth(b),d.monthNamesShort,d.monthNames);break;case"y":i=d.getYear(b),a+=j("y")?i:(i%100<10?"0":"")+i%100;break;case"h":h=b.getHours(),a+=f("h",12<h?h-12:0===h?12:h,2);break;case"H":a+=f("H",b.getHours(),2);break;case"i":a+=f("i",b.getMinutes(),2);break;case"s":a+=f("s",b.getSeconds(),2);break;case"a":a+=11<b.getHours()?d.pmText:d.amText;break;case"A":a+=11<b.getHours()?d.pmText.toUpperCase():d.amText.toUpperCase();break;case"'":j("'")?a+="'":k=!0;break;default:a+=g.charAt(e)}return a}function V(l,b,y){var a=c({},az,y),h=g(a.defaultValue||new Date),e,r,k,j,i,t,d,x,w,m,v,n,s,o;if(!l||!b)return h;if(b.getTime)return b;b="object"==Z(b)?b.toString():b+"";function q(b){var a=e+1<l.length&&l.charAt(e+1)==b;return a&&e++,a}function f(a){q(a);var d=new RegExp("^\\d{1,"+("@"==a?14:"!"==a?20:"y"==a?4:"o"==a?3:2)+"}"),c=b.substr(n).match(d);return c?(n+=c[0].length,parseInt(c[0],10)):0}function p(d,e,f){var a,c=q(d)?f:e;for(a=0;a<c.length;a++)if(b.substr(n,c[a].length).toLowerCase()==c[a].toLowerCase())return n+=c[a].length,a+1;return 0}function u(){n++}r=a.shortYearCutoff,k=a.getYear(h),j=a.getMonth(h)+1,i=a.getDay(h),t=-1,d=h.getHours(),x=h.getMinutes(),w=0,m=-1,v=!1,n=0;for(e=0;e<l.length;e++)if(v)"'"!=l.charAt(e)||q("'")?u():v=!1;else switch(l.charAt(e)){case"d":i=f("d");break;case"D":p("D",a.dayNamesShort,a.dayNames);break;case"o":t=f("o");break;case"m":j=f("m");break;case"M":j=p("M",a.monthNamesShort,a.monthNames);break;case"y":k=f("y");break;case"H":d=f("H");break;case"h":d=f("h");break;case"i":x=f("i");break;case"s":w=f("s");break;case"a":m=p("a",[a.amText,a.pmText],[a.amText,a.pmText])-1;break;case"A":m=p("A",[a.amText,a.pmText],[a.amText,a.pmText])-1;break;case"'":q("'")?u():v=!0;break;default:u()}if(k<100&&(k+=(new Date).getFullYear()-(new Date).getFullYear()%100+(k<=("string"!=typeof r?r:(new Date).getFullYear()%100+parseInt(r,10))?0:-100)),-1<t){j=1,i=t;do s=32-new Date(k,j-1,32,12).getDate(),s<i&&(j++,i-=s);while(s<i)}return d=-1==m?d:m&&d<12?d+12:m||12!=d?d:0,o=a.getDate(k,j-1,i,d,x,w),a.getYear(o)!=k||a.getMonth(o)+1!=j||a.getDay(o)!=i?h:o}function cH(a,b){return Math.round((b-a)/864e5)}function H(a){return O(a.getFullYear(),a.getMonth(),a.getDate())}function ap(a){return a.getFullYear()+"-"+(a.getMonth()+1)+"-"+a.getDate()}function cP(e,f,b){var a,c,d={y:1,m:2,d:3,h:4,i:5,s:6,u:7,tz:8};if(b)for(a in d)(c=e[d[a]-f])&&(b[a]="tz"==a?c:1)}function ad(a,b,e){var d=window.moment||b.moment,c=b.returnFormat;if(a){if("moment"==c&&d)return d(a);if("locale"==c)return j(e,a,b);if("iso8601"==c)return function(b,a){var c="",d="";return b&&(a.h&&(d+=u(b.getHours())+":"+u(b.getMinutes()),a.s&&(d+=":"+u(b.getSeconds())),a.u&&(d+="."+u(b.getMilliseconds(),3)),a.tz&&(d+=a.tz)),a.y?(c+=b.getFullYear(),a.m&&(c+="-"+u(b.getMonth()+1),a.d&&(c+="-"+u(b.getDate())),a.h&&(c+="T"+d))):a.h&&(c=d)),c}(a,b.isoParts)}return a}function g(b,d,e,c){var a;return b?b.getTime?b:b.toDate?b.toDate():("string"==typeof b&&(b=b.trim()),(a=bn.exec(b))?(cP(a,2,c),new Date(1970,0,1,a[2]?+a[2]:0,a[3]?+a[3]:0,a[4]?+a[4]:0,a[5]?+a[5]:0)):(a=a||cU.exec(b))?(cP(a,0,c),new Date(a[1]?+a[1]:1970,a[2]?a[2]-1:0,a[3]?+a[3]:1,a[4]?+a[4]:0,a[5]?+a[5]:0,a[6]?+a[6]:0,a[7]?+a[7]:0)):V(d,b,e)):null}function t(a,b){return a.getFullYear()==b.getFullYear()&&a.getMonth()==b.getMonth()&&a.getDate()==b.getDate()}cU=/^(\d{4}|[+\-]\d{6})(?:-(\d{2})(?:-(\d{2}))?)?(?:T(\d{2}):(\d{2})(?::(\d{2})(?:\.(\d{3}))?)?((Z)|([+\-])(\d{2})(?::(\d{2}))?)?)?$/,bn=/^((\d{2}):(\d{2})(?::(\d{2})(?:\.(\d{3}))?)?(?:(Z)|([+\-])(\d{2})(?::(\d{2}))?)?)?$/,M=/^\d{1,2}(\/\d{1,2})?$/,A=/^w\d$/i,az={shortYearCutoff:"+10",monthNames:["January","February","March","April","May","June","July","August","September","October","November","December"],monthNamesShort:["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],dayNames:["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],dayNamesShort:["Sun","Mon","Tue","Wed","Thu","Fri","Sat"],dayNamesMin:["S","M","T","W","T","F","S"],amText:"am",pmText:"pm",getYear:function(a){return a.getFullYear()},getMonth:function(a){return a.getMonth()},getDay:function(a){return a.getDate()},getDate:O,getMaxDayOfMonth:function(a,b){return 32-new Date(a,b,32,12).getDate()},getWeekNumber:function(a){(a=new Date(a)).setHours(0,0,0),a.setDate(a.getDate()+4-(a.getDay()||7));var b=new Date(a.getFullYear(),0,1);return Math.ceil(((a-b)/864e5+1)/7)}};function dw(a){var b;for(b in a)if(void 0!==ag[a[b]])return!0;return!1}function N(c,b){if("touchstart"==c.type)a(b).attr("data-touch","1");else if(a(b).attr("data-touch"))return a(b).removeAttr("data-touch"),!1;return!0}function bO(d,e){var b,c=getComputedStyle(d[0]);return a.each(["t","webkitT","MozT","OT","msT"],function(d,a){if(void 0!==c[a+"ransform"])return b=c[a+"ransform"],!1}),b=b.split(")")[0].split(", "),e?b[13]||b[5]:b[12]||b[4]}function dg(b){if(b){if(aN[b])return aN[b];var d=a('<div style="background-color:'+b+';"></div>').appendTo("body"),c=getComputedStyle(d[0]).backgroundColor.replace(/rgb|rgba|\(|\)|\s/g,"").split(","),e=.299*c[0]+.587*c[1]+.114*c[2]<130?"#fff":"#000";return d.remove(),aN[b]=e}}function dy(e,d,f,b){var c=a(e);f?(c.scrollTop(d),b&&b()):function g(d,e,a,b,c){var h=Math.min(1,(new Date-e)/468),i=.5*(1-Math.cos(Math.PI*h)),f=a+(b-a)*i;d.scrollTop(f),f!==b?E(function(){g(d,e,a,b,c)}):c&&c()}(c,new Date,c.scrollTop(),d,b)}ae.datetime={formatDate:j,parseDate:V},aN={};function aO(o,u,s,y,x,j){var b,f,g,c,n,q,p,h,m,w=y||d;function t(d){var i;b=a(this),h=+b.attr("data-step"),g=+b.attr("data-index"),f=!0,x&&d.stopPropagation(),"touchstart"==d.type&&b.closest(".mbsc-no-touch").removeClass("mbsc-no-touch"),"mousedown"==d.type&&d.preventDefault(),i="keydown"!=d.type?(q=e(d,"X"),p=e(d,"Y"),N(d,this)):32===d.keyCode,c||!i||b.hasClass("mbsc-disabled")||(r(g,h,d)&&(b.addClass("mbsc-active"),j&&j.addRipple(b.find(".mbsc-segmented-content"),d)),"mousedown"==d.type&&a(document).on("mousemove",k).on("mouseup",l))}function k(a){(7<Math.abs(q-e(a,"X"))||7<Math.abs(p-e(a,"Y")))&&(f=!0,i())}function l(b){"touchend"==b.type&&b.preventDefault(),i(),"mouseup"==b.type&&a(document).off("mousemove",k).off("mouseup",l)}function i(){c=!1,clearInterval(m),b&&(b.removeClass("mbsc-active"),j&&setTimeout(function(){j.removeRipple()},100))}function r(a,b,d){return c||w(a)||(g=a,h=b,n=d,f=!(c=!0),setTimeout(v,100)),c}function v(){b&&b.hasClass("mbsc-disabled")?i():(!c&&f||(f=!0,u(g,h,n,v)),c&&s&&(clearInterval(m),m=setInterval(function(){u(g,h,n)},s)))}return o.on("touchstart mousedown keydown",t).on("touchmove",k).on("touchend touchcancel keyup",l),{start:r,stop:i,destroy:function(){o.off("touchstart mousedown keydown",t).off("touchmove",k).off("touchend touchcancel keyup",l)}}}q&&(ag=document.createElement("modernizr").style,y=function(){var a,b=["Webkit","Moz","O","ms"];for(a in b)if(dw([b[a]+"Transform"]))return"-"+b[a].toLowerCase()+"-";return""}(),n=y.replace(/^\-/,"").replace(/\-$/,"").replace("moz","Moz"),P=void 0!==ag.animation?"animationend":"webkitAnimationEnd",aL=void 0!==ag.transition,bR=(bs="ios"===m&&!cW)&&window.webkit&&window.webkit.messageHandlers,Y=void 0===ag.touchAction||bs&&!bR),aP="position:absolute;left:0;top:0;",bW=aP+"right:0;bottom:0;overflow:hidden;z-index:-1;",dl='<div style="'+bW+'"><div style="'+aP+'"></div></div><div style="'+bW+'"><div style="'+aP+'width:200%;height:200%;"></div></div>';function dk(m,n,h){function l(){d.style.width="100000px",d.style.height="100000px",a.scrollLeft=1e5,a.scrollTop=1e5,c.scrollLeft=1e5,c.scrollTop=1e5}function e(){var b=new Date;g=0,i||(200<b-j&&!a.scrollTop&&!a.scrollLeft&&(j=b,l()),g=g||E(e))}function k(){f=f||E(o)}function o(){f=0,l(),n()}var a,d,g,f,c,i,j=0,b=document.createElement("div");return b.innerHTML=dl,b.dir="ltr",c=b.childNodes[1],a=b.childNodes[0],d=a.childNodes[0],m.appendChild(b),a.addEventListener("scroll",k),c.addEventListener("scroll",k),h?h.runOutsideAngular(function(){E(e)}):E(e),{detach:function(){m.removeChild(b),i=!0}}}function dx(a){a.preventDefault()}function z(S,B,ae){var r,Y,x,g,L,U,y,o,T,m,u,i,k,s,h,R,aa,q,v,n,D,w,F,N,t,V,J,f,M,K,l,z,G,c=this,j=a(S),H=[],O=new Date;function af(b){u&&u.removeClass("mbsc-active"),(u=a(this)).hasClass("mbsc-disabled")||u.hasClass("mbsc-fr-btn-nhl")||u.addClass("mbsc-active"),"mousedown"===b.type?a(document).on("mouseup",A):"pointerdown"===b.type&&a(document).on("pointerup",A)}function A(b){u&&(u.removeClass("mbsc-active"),u=null),"mouseup"===b.type?a(document).off("mouseup",A):"pointerup"===b.type&&a(document).off("pointerup",A)}function Z(d){b.activeInstance==c&&(13!=d.keyCode||a(d.target).is('textarea,button,input[type="button"],input[type="submit"]')&&!d.shiftKey?27==d.keyCode&&c.cancel():c.select())}function _(a){a||bJ||!c._activeElm||(O=new Date,c._activeElm.focus())}function $(e){var d=aC,b=f.focusOnClose;c._markupRemove(),g.remove(),h&&(i.mbscModals--,f.scrollLock&&i.mbscLock--,i.mbscLock||x.removeClass("mbsc-fr-lock"),w&&(i.mbscIOSLock--,i.mbscIOSLock||(x.removeClass("mbsc-fr-lock-ios"),r.css({top:"",left:""}),o.scrollLeft(i.mbscScrollLeft),o.scrollTop(i.mbscScrollTop))),i.mbscModals||x.removeClass("mbsc-fr-lock-ctx"),i.mbscModals&&!J||e||(d=d||j,setTimeout(function(){void 0===b||!0===b?(aA=!0,d[0].focus()):b&&a(b)[0].focus()},200))),J=void 0,R=!1,l("onHide")}function ag(){clearTimeout(V),V=setTimeout(function(){c.position(!0)&&(t.style.visibility="hidden",t.offsetHeight,t.style.visibility="")},200)}function ab(a){b.activeInstance==c&&a.target.nodeType&&!N.contains(a.target)&&100<new Date-O&&(O=new Date,c._activeElm.focus())}function ac(s,p){var i,d,b,o,n;h?g.appendTo(r):j.is("div")&&!c._hasContent?j.empty().append(g):j.hasClass("mbsc-control")?(i=j.closest(".mbsc-control-w"),g.insertAfter(i),i.hasClass("mbsc-select")&&i.addClass("mbsc-select-inline")):g.insertAfter(j),R=!0,c._markupInserted(g),l("onMarkupInserted",{target:q}),h&&f.closeOnOverlayTap&&L.on("touchstart mousedown",function(a){b||a.target!=N||(d=!(b=!0),o=e(a,"X"),n=e(a,"Y"))}).on("touchmove mousemove",function(a){b&&!d&&(9<Math.abs(e(a,"X")-o)||9<Math.abs(e(a,"Y")-n))&&(d=!0)}).on("touchcancel",function(){b=!1}).on("touchend click",function(a){b&&!d&&(c.cancel(),"touchend"==a.type&&Q()),b=!1}),g.on("mousedown",".mbsc-btn-e,.mbsc-fr-btn-e",dx).on("touchstart mousedown",function(a){f.stopProp&&a.stopPropagation()}).on("keydown",".mbsc-fr-btn-e",function(a){32==a.keyCode&&(a.preventDefault(),a.stopPropagation(),this.click())}).on("keydown",function(b){if(32!=b.keyCode||a(b.target).is(bZ)){if(9==b.keyCode&&h&&f.focusTrap){var c=g.find('input,select,textarea,button,[tabindex="0"]').filter(function(){return 0<this.offsetWidth||0<this.offsetHeight}),i=c.index(a(":focus",g)),d=c.length-1,e=0;b.shiftKey&&(d=0,e=-1),i===d&&(c.eq(e)[0].focus(),b.preventDefault())}}else b.preventDefault()}).on("touchstart mousedown pointerdown",".mbsc-fr-btn-e",af).on("touchend",".mbsc-fr-btn-e",A),q.addEventListener("touchstart",function(){K||(K=!0,r.find(".mbsc-no-touch").removeClass("mbsc-no-touch"))},!0),a.each(m,function(d,b){c.tap(a(".mbsc-fr-btn"+d,g),function(a){b=I(b)?c.buttons[b]:b,(I(b.handler)?c.handlers[b.handler]:b.handler).call(this,a,c)},!0)}),c._attachEvents(g),!1!==c.position()&&((h||c._checkSize)&&(F=dk(q,ag,f.zone)),h&&(g.removeClass("mbsc-fr-pos"),k&&!s?g.addClass("mbsc-anim-in mbsc-anim-trans mbsc-anim-trans-"+k).on(P,function a(){g.off(P,a).removeClass("mbsc-anim-in mbsc-anim-trans mbsc-anim-trans-"+k).find(".mbsc-fr-popup").removeClass("mbsc-anim-"+k),_(p)}).find(".mbsc-fr-popup").addClass("mbsc-anim-"+k):_(p)),l("onShow",{target:q,valueText:c._tempValue}))}function E(a,b){c._isVisible||(a&&a(),!1!==c.show()&&(aC=b))}function X(){c._fillValue(),l("onSet",{valueText:c._value})}function W(){l("onCancel",{valueText:c._value})}function ad(){c.setVal(null,!0)}C.call(this,S,B,!0),c.position=function(W){var B,I,H,V,w,N,L,J,E,F,S,C,b,d,Q,P,A,k,u,K={},m=0,e=0,x=0,O=0;if(!R)return!1;if(P=z,Q=G,b=q.offsetHeight,(d=q.offsetWidth)&&b&&(z!==d||G!==b||!W)){if(c._checkResp(d))return!1;if(z=d,G=b,c._isFullScreen||/top|bottom/.test(f.display)?y.width(d):h&&T.width(""),c._position(g),!c._isFullScreen&&/center|bubble/.test(f.display)&&(a(".mbsc-w-p",g).each(function(){A=this.getBoundingClientRect().width,O+=A,x=x<A?A:x}),C=d-16<O||!0===f.tabs,T.css({width:c._isLiquid?Math.min(f.maxPopupWidth,d-16):Math.ceil(C?x:O),"white-space":C?"":"nowrap"})),!1!==l("onPosition",{target:q,popup:t,hasTabs:C,oldWidth:P,oldHeight:Q,windowWidth:d,windowHeight:b})&&h)return D&&(m=o.scrollLeft(),e=o.scrollTop(),z&&U.css({width:"",height:""})),v=t.offsetWidth,n=t.offsetHeight,M=n<=b&&v<=d,"center"==f.display?(u=Math.max(0,m+(d-v)/2),k=Math.max(0,e+(b-n)/2)):"bubble"==f.display?(B=void 0===f.anchor?j:a(f.anchor),L=a(".mbsc-fr-arr-i",g)[0],w=(V=B.offset()).top+(s?e-r.offset().top:0),N=V.left+(s?m-r.offset().left:0),I=B[0].offsetWidth,H=B[0].offsetHeight,J=L.offsetWidth,E=L.offsetHeight,u=p(N-(v-I)/2,m+3,m+d-v-3),e+b<(k=w+H+E/2)+n+8&&e<w-n-E/2?(y.removeClass("mbsc-fr-bubble-bottom").addClass("mbsc-fr-bubble-top"),k=w-n-E/2):y.removeClass("mbsc-fr-bubble-top").addClass("mbsc-fr-bubble-bottom"),a(".mbsc-fr-arr",g).css({left:p(N+I/2-(u+(v-J)/2),0,J)}),M=e<k&&m<u&&k+n<=e+b&&u+v<=m+d):(u=m,k="top"==f.display?e:Math.max(0,e+b-n)),D&&(F=Math.max(k+n,s?i.scrollHeight:a(document).height()),S=Math.max(u+v,s?i.scrollWidth:a(document).width()),U.css({width:S,height:F}),f.scroll&&"bubble"==f.display&&(e+b<k+n+8||e+b<w||w+H<e)&&o.scrollTop(Math.min(w,k+n-b+8,F-b))),K.top=Math.floor(k),K.left=Math.floor(u),y.css(K),!0}},c.attachShow=function(g,e){var d,b=a(g).off(".mbsc"),h=b.prop("readonly");"inline"!==f.display&&((f.showOnFocus||f.showOnTap)&&b.is("input,select")&&(b.prop("readonly",!0).on("mousedown.mbsc",function(a){a.preventDefault()}).on("focus.mbsc",function(){c._isVisible&&this.blur()}),(d=a('label[for="'+b.attr("id")+'"]')).length||(d=b.closest("label"))),b.is("select")||(f.showOnFocus&&b.on("focus.mbsc",function(){aA?aA=!1:E(e,b)}),f.showOnTap&&(b.on("keydown.mbsc",function(a){32!=a.keyCode&&13!=a.keyCode||(a.preventDefault(),a.stopPropagation(),E(e,b))}),c.tap(b,function(a){a.isMbscTap&&(K=!0),E(e,b)}),d&&d.length&&c.tap(d,function(a){a.preventDefault(),a.target!==b[0]&&E(e,b)}))),H.push({readOnly:h,el:b,lbl:d}))},c.select=function(){h?c.hide(!1,"set",!1,X):X()},c.cancel=function(){h?c.hide(!1,"cancel",!1,W):W()},c.clear=function(){c._clearValue(),l("onClear"),h&&c._isVisible&&!c.live?c.hide(!1,"clear",!1,ad):ad()},c.enable=function(){f.disabled=!1,a.each(H,function(b,a){a.el.is("input,select")&&(a.el[0].disabled=!1)})},c.disable=function(){f.disabled=!0,a.each(H,function(b,a){a.el.is("input,select")&&(a.el[0].disabled=!0)})},c.show=function(A,B){var n,e,u,v,d,j,p,C;if(!f.disabled&&!c._isVisible){if(c._readValue(),!1===l("onBeforeShow"))return!1;if(aC=null,k=f.animate,m=f.buttons||[],D=s||"bubble"==f.display,w=da&&!D&&f.scrollLock,n=0<m.length,!1!==k&&("top"==f.display?k=k||"slidedown":"bottom"==f.display?k=k||"slideup":"center"!=f.display&&"bubble"!=f.display||(k=k||"pop")),h&&(G=z=0,w&&!x.hasClass("mbsc-fr-lock-ios")&&(i.mbscScrollTop=v=Math.max(0,o.scrollTop()),i.mbscScrollLeft=u=Math.max(0,o.scrollLeft()),r.css({top:-v+"px",left:-u+"px"})),x.addClass((f.scrollLock?"mbsc-fr-lock":"")+(w?" mbsc-fr-lock-ios":"")+(s?" mbsc-fr-lock-ctx":"")),a(document.activeElement).is("input,textarea")&&document.activeElement.blur(),J=b.activeInstance,b.activeInstance=c,i.mbscModals=(i.mbscModals||0)+1,w&&(i.mbscIOSLock=(i.mbscIOSLock||0)+1),f.scrollLock&&(i.mbscLock=(i.mbscLock||0)+1)),e='<div lang="'+f.lang+'" class="mbsc-fr mbsc-'+f.theme+(f.baseTheme?" mbsc-"+f.baseTheme:"")+" mbsc-fr-"+f.display+" "+(f.cssClass||"")+" "+(f.compClass||"")+(c._isLiquid?" mbsc-fr-liq":"")+(h?" mbsc-fr-pos"+(f.showOverlay?"":" mbsc-fr-no-overlay"):"")+(aa?" mbsc-fr-pointer":"")+(cZ?" mbsc-fr-hb":"")+(K?"":" mbsc-no-touch")+(w?" mbsc-platform-ios":"")+(n?3<=m.length?" mbsc-fr-btn-block ":"":" mbsc-fr-nobtn")+'">'+(h?'<div class="mbsc-fr-persp">'+(f.showOverlay?'<div class="mbsc-fr-overlay"></div>':"")+'<div role="dialog" class="mbsc-fr-scroll">':"")+'<div class="mbsc-fr-popup'+(f.rtl?" mbsc-rtl":" mbsc-ltr")+(f.headerText?" mbsc-fr-has-hdr":"")+'">'+("bubble"===f.display?'<div class="mbsc-fr-arr-w"><div class="mbsc-fr-arr-i"><div class="mbsc-fr-arr"></div></div></div>':"")+(h?'<div class="mbsc-fr-focus" tabindex="-1"></div>':"")+'<div class="mbsc-fr-w">'+(f.headerText?'<div class="mbsc-fr-hdr">'+(I(f.headerText)?f.headerText:"")+"</div>":"")+'<div class="mbsc-fr-c">',e+=c._generateContent(),e+="</div>",n){C=m.length;for(e+='<div class="mbsc-fr-btn-cont">',j=0;j<m.length;j++)p=f.btnReverse?C-j-1:j,"set"===(d=I(d=m[p])?c.buttons[d]:d).handler&&(d.parentClass="mbsc-fr-btn-s"),"cancel"===d.handler&&(d.parentClass="mbsc-fr-btn-c"),e+="<div"+(f.btnWidth?' style="width:'+100/m.length+'%"':"")+' class="mbsc-fr-btn-w '+(d.parentClass||"")+'"><div tabindex="0" role="button" class="mbsc-fr-btn'+p+" mbsc-fr-btn-e "+(void 0===d.cssClass?f.btnClass:d.cssClass)+(d.icon?" mbsc-ic mbsc-ic-"+d.icon:"")+'">'+(d.text||"")+"</div></div>";e+="</div>"}g=a(e+="</div></div></div></div>"+(h?"</div></div>":"")),U=a(".mbsc-fr-persp",g),L=a(".mbsc-fr-scroll",g),T=a(".mbsc-fr-w",g),y=a(".mbsc-fr-popup",g),Y=a(".mbsc-fr-hdr",g),q=g[0],N=L[0],t=y[0],c._activeElm=a(".mbsc-fr-focus",g)[0],c._markup=g,c._isVisible=!0,c.markup=q,c._markupReady(g),l("onMarkupReady",{target:q}),h&&(a(window).on("keydown",Z),f.scrollLock&&g.on("touchmove mousewheel wheel",function(a){M&&a.preventDefault()}),f.focusTrap&&o.on("focusin",ab)),h?setTimeout(function(){ac(A,B)},w?100:0):ac(A,B)}},c.hide=function(d,e,f,i){if(!c._isVisible||!f&&!c._isValid&&"set"==e||!f&&!1===l("onBeforeClose",{valueText:c._tempValue,button:e}))return!1;c._isVisible=!1,F&&(F.detach(),F=null),h&&(a(document.activeElement).is("input,textarea")&&t.contains(document.activeElement)&&document.activeElement.blur(),b.activeInstance==c&&(b.activeInstance=J),a(window).off("keydown",Z),o.off("focusin",ab)),g&&(h&&k&&!d?g.addClass("mbsc-anim-out mbsc-anim-trans mbsc-anim-trans-"+k).on(P,function a(){g.off(P,a),$(d)}).find(".mbsc-fr-popup").addClass("mbsc-anim-"+k):$(d),c._detachEvents(g)),i&&i(),j.trigger("blur"),l("onClose",{valueText:c._value})},c.isVisible=function(){return c._isVisible},c.setVal=d,c.getVal=d,c._generateContent=d,c._attachEvents=d,c._detachEvents=d,c._readValue=d,c._clearValue=d,c._fillValue=d,c._markupReady=d,c._markupInserted=d,c._markupRemove=d,c._position=d,c.__processSettings=d,c.__init=d,c.__destroy=d,c._destroy=function(){c.hide(!0,!1,!0),j.off(".mbsc"),a.each(H,function(b,a){a.el.off(".mbsc").prop("readonly",a.readOnly),a.lbl&&a.lbl.off(".mbsc")}),c.__destroy()},c._updateHeader=function(){var a=f.headerText,b=a?"function"==typeof a?a.call(S,c._tempValue):a.replace(/\{value\}/i,c._tempValue):"";Y.html(b||"&nbsp;")},c._getRespCont=function(){return s="body"!=f.context,o=a(s?f.context:window),"inline"==f.display?j.is("div")?j:j.parent():o},c._processSettings=function(b){var e,d;for(c.__processSettings(b),(aa=!f.touchUi)&&(f.display=b.display||B.display||"bubble",f.buttons=b.buttons||B.buttons||[],f.showOverlay=b.showOverlay||B.showOverlay||!1),f.buttons=f.buttons||("inline"!==f.display?["cancel","set"]:[]),f.headerText=void 0===f.headerText?"inline"!==f.display&&"{value}":f.headerText,m=f.buttons||[],h="inline"!==f.display,r=a(f.context),x=s?r:a("body,html"),i=r[0],c.live=!0,d=0;d<m.length;d++)"ok"!=(e=m[d])&&"set"!=e&&"set"!=e.handler||(c.live=!1);c.buttons.set={text:f.setText,icon:f.setIcon,handler:"set"},c.buttons.cancel={text:f.cancelText,icon:f.cancelIcon,handler:"cancel"},c.buttons.close={text:f.closeText,icon:f.closeIcon,handler:"cancel"},c.buttons.clear={text:f.clearText,icon:f.clearIcon,handler:"clear"},c._isInput=j.is("input")},c._init=function(b){var a=c._isVisible,d=a&&!g.hasClass("mbsc-fr-pos");a&&c.hide(!0,!1,!0),j.off(".mbsc"),c.__init(b),c._isLiquid="liquid"==f.layout,h?(c._readValue(),c._hasContent||f.skipShow||c.attachShow(j),a&&c.show(d)):c.show(),j.removeClass("mbsc-cloak").filter("input, select, textarea").on("change.mbsc",function(){c._preventChange||c.setVal(j.val(),!0,!1),c._preventChange=!1})},c.buttons={},c.handlers={set:c.select,cancel:c.cancel,clear:c.clear},c._value=null,c._isValid=!0,c._isVisible=!1,f=c.settings,l=c.trigger,ae||c.init()}aX=b.themes,da=/(iphone|ipod)/i.test(aj)&&7<=x,bJ="android"==m,aS="ios"==m,cZ=aS&&7<x,bZ="input,select,textarea,button",z.prototype._defaults={lang:"en",setText:"Set",selectedText:"{count} selected",closeText:"Close",cancelText:"Cancel",clearText:"Clear",context:"body",maxPopupWidth:600,disabled:!1,closeOnOverlayTap:!0,showOnFocus:bJ||aS,showOnTap:!0,display:"center",scroll:!0,scrollLock:!0,showOverlay:!0,tap:!0,touchUi:!0,btnClass:"mbsc-fr-btn",btnWidth:!0,focusTrap:!0,focusOnClose:!(aS&&8==x)},l.Frame=z,aX.frame.mobiscroll={headerText:!1,btnWidth:!1},aX.scroller.mobiscroll=c({},aX.frame.mobiscroll,{rows:5,showLabel:!1,selectedLineBorder:1,weekDays:"min",checkIcon:"ion-ios7-checkmark-empty",btnPlusClass:"mbsc-ic mbsc-ic-arrow-down5",btnMinusClass:"mbsc-ic mbsc-ic-arrow-up5",btnCalPrevClass:"mbsc-ic mbsc-ic-arrow-left5",btnCalNextClass:"mbsc-ic mbsc-ic-arrow-right5"}),q&&a(window).on("focus",function(){aC&&(aA=!0)});function X(Q,am,av){var v,ab,M,ag,m,W,H,d,ad,L,O,ac,ah,i,G,aa,j,q,l,K,A,J,U,z,u,D,ai,au,t,ap,w,S,g,o,k,aq,Z,P,X,x,$,ak,r,c,h,f=this,F=0,af=1,b=am,B=a(Q);function aj(g){r("onStart",{domEvent:g}),b.stopProp&&g.stopPropagation(),b.prevDef&&g.preventDefault(),b.readonly||b.lock&&A||N(g,this)&&!K&&(v&&v.removeClass("mbsc-active"),G=!1,A||(v=a(g.target).closest(".mbsc-btn-e",this)).length&&!v.hasClass("mbsc-disabled")&&(G=!0,ag=setTimeout(function(){v.addClass("mbsc-active")},100)),J=t=!(K=!0),f.scrolled=A,Z=e(g,"X"),P=e(g,"Y"),ac=Z,d=H=W=0,aq=new Date,k=+bO(x,c)||0,A&&T(k,cX?0:1),"mousedown"===g.type&&a(document).on("mousemove",V).on("mouseup",R))}function V(a){K&&(b.stopProp&&a.stopPropagation(),ac=e(a,"X"),ah=e(a,"Y"),W=ac-Z,H=ah-P,d=c?H:W,G&&(Math.abs(H)>b.thresholdY||Math.abs(W)>b.thresholdX)&&(clearTimeout(ag),v.removeClass("mbsc-active"),G=!1),(f.scrolled||!J&&Math.abs(d)>ak)&&(t||r("onGestureStart",i),f.scrolled=t=!0,z||(z=!0,U=E(al))),c||b.scrollLock?a.preventDefault():f.scrolled?a.preventDefault():7<Math.abs(H)&&(J=!0,f.scrolled=!0,R()))}function al(){q&&(d=p(d,-g*q,g*q)),T(p(k+d,l-O,j+O)),z=!1}function R(c){if(K){var e,g=new Date-aq;b.stopProp&&c&&c.stopPropagation(),bd(U),z=!1,!J&&f.scrolled&&(b.momentum&&g<300&&(e=d/g,d=Math.max(Math.abs(d),e*e/b.speedUnit)*(d<0?-1:1)),ae(d)),G&&(clearTimeout(ag),v.addClass("mbsc-active"),setTimeout(function(){v.removeClass("mbsc-active")},100),J||f.scrolled||r("onBtnTap",{target:v[0],domEvent:c})),c&&"mouseup"==c.type&&a(document).off("mousemove",V).off("mouseup",R),K=!1}}function an(a){if(a=a.originalEvent||a,d=c?null==a.deltaY?a.wheelDelta||a.detail:a.deltaY:a.deltaX,r("onStart",{domEvent:a}),b.stopProp&&a.stopPropagation(),d){if(a.preventDefault(),a.deltaMode&&1==a.deltaMode&&(d*=15),d=p(-d,-w,w),k=h,b.readonly)return;if(t||at(),k+d<l&&(k=l,d=0),j<k+d&&(k=j,d=0),z||(z=!0,U=E(al)),!d&&t)return;t=!0,clearTimeout(ap),ap=setTimeout(function(){bd(U),t=z=!1,ae(d)},200)}}function ao(c){r("onStart",{domEvent:c}),b.readonly||(c.stopPropagation(),k=h,t=!1,c.target==u?(P=e(c,"Y",!0),a(document).on("mousemove",Y).on("mouseup",_)):(P=ab.offset().top,Y(c),_()))}function Y(b){var a=(e(b,"Y",!0)-P)/m;d=aa?p(d=-(q*g*2+m)*a,-g*q,g*q):(l-j-m)*a,t||at(),t=!0,T(p(k+d,l-O,j+O))}function _(){k=h,ae(0),a(document).off("mousemove",Y).off("mouseup",_)}function ar(a){a.stopPropagation()}function at(){r("onGestureStart",i={posX:c?0:h,posY:c?h:0,originX:c?0:k,originY:c?k:0,direction:0<d?c?270:360:c?90:180})}function ae(e){var d,n,a;if(q&&(e=p(e,-g*q,g*q)),a=p(Math.round((k+e)/g)*g,l,j),o){if(e<0){for(d=o.length-1;0<=d;d--)if(Math.abs(a)+m>=o[d].breakpoint){af=2,a=o[F=d].snap2;break}}else if(0<=e)for(d=0;d<o.length;d++)if(Math.abs(a)<=o[d].breakpoint){af=1,a=o[F=d].snap1;break}a=p(a,l,j)}n=b.time||(h<l||j<h?1e3:Math.max(1e3,Math.abs(a-h)*b.timeUnit)),i.destinationX=c?0:a,i.destinationY=c?a:0,i.duration=n,i.transitionTiming=L,r("onGestureEnd",i),f.scroll(a,n)}function T(a,d,v,o){function e(){clearInterval(S),clearTimeout($),A=!1,h=a,i.posX=c?0:a,i.posY=c?a:0,s&&r("onMove",i),t&&r("onAnimationEnd",i),o&&o()}var p,s=a!=h,t=1<d,f=d?y+"transform "+Math.round(d)+"ms "+L:"";i={posX:c?0:h,posY:c?h:0,originX:c?0:k,originY:c?k:0,direction:0<a-h?c?270:360:c?90:180},h=a,t&&(i.destinationX=c?0:a,i.destinationY=c?a:0,i.duration=d,i.transitionTiming=L,r("onAnimationStart",i)),X[n+"Transition"]=f,X[n+"Transform"]="translate3d("+(c?"0,"+a+"px,":a+"px,0,")+"0)",u&&D&&(p=aa?(ai-a)/(q*g*2):(a-j)/(l-j),u.style[n+"Transition"]=f,u.style[n+"Transform"]="translate3d(0,"+Math.max(0,Math.min((m-D)*p,m-D))+"px,0)"),!s&&!A||!d||d<=1?e():d&&(A=!v,clearInterval(S),S=setInterval(function(){var b=+bO(x,c)||0;i.posX=c?0:b,i.posY=c?b:0,r("onMove",i),Math.abs(b-a)<2&&e()},100),clearTimeout($),$=setTimeout(function(){e()},d)),b.sync&&b.sync(a,d,L)}C.call(this,Q,am,!0),f.scrolled=!1,f.scroll=function(b,c,d,e){b=p(b=s(b)?Math.round(b/g)*g:Math.ceil((a(b,Q).length?Math.round(x.offset()[ad]-a(b,Q).offset()[ad]):h)/g)*g,l,j),F=Math.round(b/g),k=h,ai=q*g+b,T(b,c,d,e)},f.refresh=function(d){var a;for(m=(void 0===b.contSize?c?B.height():B.width():b.contSize)||0,j=(void 0===b.maxScroll?0:b.maxScroll)||0,l=Math.min(j,void 0===b.minScroll?Math.min(0,c?m-x.height():m-x.width()):b.minScroll)||0,o=null,!c&&b.rtl&&(a=j,j=-l,l=-a),I(b.snap)&&(o=[],x.find(b.snap).each(function(){var a=c?this.offsetTop:this.offsetLeft,b=c?this.offsetHeight:this.offsetWidth;o.push({breakpoint:a+b/2,snap1:-a,snap2:m-a-b})})),g=s(b.snap)?b.snap:1,q=b.snap?b.maxSnapScroll:0,L=b.easing,O=b.elastic?s(b.snap)?g:s(b.elastic)?b.elastic:0:0,w=g;44<w;)w/=2;w=Math.round(44/w)*w,u&&(aa=l==-1/0||j==1/0,D=l<j?Math.max(20,m*m/(j-l+m)):0,u.style.height=D+"px",au.style.height=D?"":0),void 0===h&&(h=b.initialPos,F=Math.round(h/g)),d||f.scroll(b.snap?o?o[F]["snap"+af]:F*g:h)},f._processSettings=function(){c="Y"==b.axis,ad=c?"top":"left",x=b.moveElement||B.children().eq(0),X=x[0].style,ak=c?b.thresholdY:b.thresholdX,b.scrollbar&&(M=b.scrollbar,ab=M.find(".mbsc-sc-bar"),u=ab[0],au=M[0])},f._init=function(){f.refresh(),B.on("touchstart mousedown",aj).on("touchmove",V).on("touchend touchcancel",R),b.mousewheel&&B.on("wheel mousewheel",an),u&&M.on("mousedown",ao).on("click",ar),Q.addEventListener("click",function(a){f.scrolled&&(f.scrolled=!1,a.stopPropagation(),a.preventDefault())},!0)},f._destroy=function(){clearInterval(S),B.off("touchstart mousedown",aj).off("touchmove",V).off("touchend touchcancel",R).off("wheel mousewheel",an),u&&M.off("mousedown",ao).off("click",ar)},b=f.settings,r=f.trigger,av||f.init()}cX="ios"==m,X.prototype={_defaults:{speedUnit:.0022,timeUnit:3,initialPos:0,axis:"Y",thresholdX:10,thresholdY:5,easing:"cubic-bezier(0.190, 1.000, 0.220, 1.000)",stopProp:!0,momentum:!0,mousewheel:!0,elastic:!0}},i={},be=q?window.CSS:null,cR=be&&be.supports&&be.supports("(transform-style: preserve-3d)");function aK(a){return(a+"").replace('"',"___")}function k(o,I,R){var L,m,E,g,l,p,v,f,e,x,F,q,d,j,t,h,B,i=40,M=1e3,b=this,D=a(o);function T(b){var c,d,e=+a(this).attr("data-index");38==b.keyCode?(c=!0,d=-1):40==b.keyCode?(c=!0,d=1):32==b.keyCode&&(c=!0,Q(e,a(b.target))),c&&(b.stopPropagation(),b.preventDefault(),d&&v.start(e,d,b))}function U(){v.stop()}function Q(c,f){var a=h[c],i=+f.attr("data-index"),e=r(a,i),g=b._tempSelected[c],k=s(a.multiple)?a.multiple:1/0;!1!==j("onItemTap",{target:f[0],index:c,value:e,selected:f.hasClass("mbsc-sc-itm-sel")})&&(a.multiple&&!a._disabled[e]&&(void 0!==g[e]?(f.removeClass(l).removeAttr("aria-selected"),delete g[e]):(1==k&&(b._tempSelected[c]=g={},a._$markup.find(".mbsc-sc-itm-sel").removeClass(l).removeAttr("aria-selected")),bb(g).length<k&&(f.addClass(l).attr("aria-selected","true"),g[e]=e))),C(a,c,i,M,a._index<i?1:2,!0,a.multiple),b.live&&(!a.multiple||1===a.multiple&&d.tapSelect)&&(!0===d.setOnTap||d.setOnTap[c])&&setTimeout(function(){b.select()},d.tapSelect?0:200))}function J(a){return-(a.max-a._offset-(a.multiple&&!g?Math.floor(d.rows/2):0))*e}function P(a){return-(a.min-a._offset+(a.multiple&&!g?Math.floor(d.rows/2):0))*e}function G(a,c){return(a._array?a._map[c]:+a.getIndex(c,b))||0}function K(c,d){var e=c.data;if(d>=c.min&&d<=c.max)return c._array?c.circular?a(e).get(d%c._length):e[d]:a.isFunction(e)?e(d,b):""}function u(b){return a.isPlainObject(b)?void 0!==b.value?b.value:b.display:b}function r(a,b){return u(K(a,b))}function S(a,b,e){var c=h[a];C(c,a,c._index+b,d.delay+100,1==b?1:2,!1,!1,"keydown"==e.type)}function O(b){return a.isArray(d.readonly)?d.readonly[b]:d.readonly}function H(b,g,j){var h=b._index-b._batch;return b.data=b.data||[],b.key=void 0!==b.key?b.key:g,b.label=void 0!==b.label?b.label:g,b._map={},b._array=a.isArray(b.data),b._array&&(b._length=b.data.length,a.each(b.data,function(a,c){b._map[u(c)]=a})),b.circular=void 0===d.circular?void 0===b.circular?b._array&&b._length>d.rows:b.circular:a.isArray(d.circular)?d.circular[g]:d.circular,b.min=b._array?b.circular?-1/0:0:void 0===b.min?-1/0:b.min,b.max=b._array?b.circular?1/0:b._length-1:void 0===b.max?1/0:b.max,b._nr=g,b._index=G(b,f[g]),b._disabled={},b._batch=0,b._current=b._index,b._first=b._index-i,b._last=b._index+i,b._offset=b._first,j?(b._offset-=b._margin/e+(b._index-h),b._margin+=(b._index-h)*e):b._margin=0,b._refresh=function(a){c(b._scroller.settings,{minScroll:J(b),maxScroll:P(b)}),b._scroller.refresh(a)},B[b.key]=b}function k(j,v,z,x,p){var h,o,c,g,n,m,r,k,i,w,q="",s=b._tempSelected[v],A=j._disabled||{};for(h=z;h<=x;h++)c=K(j,h),i=c,n=void 0===(w=a.isPlainObject(i)?i.display:i)?"":w,g=u(c),o=c&&void 0!==c.cssClass?c.cssClass:"",m=c&&void 0!==c.label?c.label:"",r=c&&c.invalid,k=void 0!==g&&g==f[v]&&!j.multiple,q+='<div role="option" tabindex="-1" aria-selected="'+!!s[g]+'" class="mbsc-sc-itm '+(p?"mbsc-sc-itm-3d ":"")+o+" "+(k?"mbsc-sc-itm-sel ":"")+(s[g]?l:"")+(void 0===g?" mbsc-sc-itm-ph":" mbsc-btn-e")+(r?" mbsc-sc-itm-inv-h mbsc-disabled":"")+(A[g]?" mbsc-sc-itm-inv mbsc-disabled":"")+'" data-index="'+h+'" data-val="'+aK(g)+'"'+(m?' aria-label="'+m+'"':"")+(k?' aria-selected="true"':"")+' style="height:'+e+"px;line-height:"+e+"px;"+(p?y+"transform:rotateX("+(j._offset-h)*E%360+"deg) translateZ("+e*d.rows/2+"px);":"")+'">'+(1<t?'<div class="mbsc-sc-itm-ml" style="line-height:'+Math.round(e/t)+"px;font-size:"+Math.round(e/t*.8)+'px;">':"")+n+(1<t?"</div>":"")+"</div>";return q}function N(n,m,k,l){var c,a=h[n],g=l||a._disabled,b=G(a,m),e=r(a,b),j=e,f=e,d=0,i=0;if(!0===g[e]){for(c=0;b-d>=a.min&&g[j]&&c<100;)c++,j=r(a,b-++d);for(c=0;b+i<a.max&&g[f]&&c<100;)c++,f=r(a,b+ ++i);e=(i<d&&i&&2!==k||!d||b-d<0||1==k)&&!g[f]?f:j}return e}function w(t,c,u,m,x,v,w){var r,n,s,k,p=b._isVisible;q=!0,k=d.validate.call(o,{values:f.slice(0),index:c,direction:u},b)||{},q=!1,k.valid&&(b._tempWheelArray=f=k.valid.slice(0)),v||a.each(h,function(h,d){if(p&&d._$markup.find(".mbsc-sc-itm-inv").removeClass("mbsc-sc-itm-inv mbsc-disabled"),d._disabled={},k.disabled&&k.disabled[h]&&a.each(k.disabled[h],function(b,a){d._disabled[a]=!0,p&&d._$markup.find('.mbsc-sc-itm[data-val="'+aK(a)+'"]').addClass("mbsc-sc-itm-inv mbsc-disabled")}),f[h]=d.multiple?f[h]:N(h,f[h],u),p){if(d.multiple&&void 0!==c||d._$markup.find(".mbsc-sc-itm-sel").removeClass(l).removeAttr("aria-selected"),n=G(d,f[h]),r=n-d._index+d._batch,Math.abs(r)>2*i+1&&(s=r+(2*i+1)*(0<r?-1:1),d._offset+=s,d._margin-=s*e,d._refresh()),d._index=n+d._batch,d.multiple){if(void 0===c)for(var j in b._tempSelected[h])d._$markup.find('.mbsc-sc-itm[data-val="'+aK(j)+'"]').addClass(l).attr("aria-selected","true")}else d._$markup.find('.mbsc-sc-itm[data-val="'+aK(f[h])+'"]').addClass("mbsc-sc-itm-sel").attr("aria-selected","true");d._$active&&d._$active.attr("tabindex",-1),d._$active=d._$markup.find('.mbsc-sc-itm[data-index="'+d._index+'"]').eq(g&&d.multiple?1:0).attr("tabindex",0),w&&c===h&&d._$active.length&&(d._$active[0].focus(),d._$scroller.parent().scrollTop(0)),d._scroller.scroll(-(n-d._offset+d._batch)*e,c===h||void 0===c?t:M,x)}}),j("onValidated",{index:c,time:t}),b._tempValue=d.formatValue.call(o,f,b),p&&b._updateHeader(),b.live&&function(b,c){var a=h[b];return a&&(!a.multiple||1!==a.multiple&&c&&(!0===d.setOnTap||d.setOnTap[b]))}(c,v)&&(b._hasValue=m||b._hasValue,A(m,m,0,!0),m&&j("onSet",{valueText:b._value})),m&&j("onChange",{index:c,valueText:b._tempValue})}function C(a,c,b,e,g,h,i,j){var d=r(a,b);void 0!==d&&(f[c]=d,a._batch=a._array?Math.floor(b/a._length)*a._length:0,a._index=b,setTimeout(function(){w(e,c,g,!0,h,i,j)},10))}function A(g,e,i,k,l){if(k?b._tempValue=d.formatValue.call(o,b._tempWheelArray,b):w(i),!l){b._wheelArray=[];for(var a=0;a<f.length;a++)b._wheelArray[a]=h[a]&&h[a].multiple?Object.keys(b._tempSelected[a]||{})[0]:f[a];b._value=b._hasValue?b._tempValue:null,b._selected=c(!0,{},b._tempSelected)}g&&(b._isInput&&D.val(b._hasValue?b._tempValue:""),j("onFill",{valueText:b._hasValue?b._tempValue:"",change:e}),e&&(b._preventChange=!0,D.trigger("change")))}z.call(this,o,I,!0),b.setVal=b._setVal=function(c,e,g,h,i){b._hasValue=null!=c,b._tempWheelArray=f=a.isArray(c)?c.slice(0):d.parseValue.call(o,c,b)||[],A(e,void 0===g?e:g,i,!1,h)},b.getVal=b._getVal=function(c){var a=b._hasValue||c?b[c?"_tempValue":"_value"]:null;return s(a)?+a:a},b.setArrayVal=b.setVal,b.getArrayVal=function(a){return a?b._tempWheelArray:b._wheelArray},b.changeWheel=function(f,h,j){var e,d;a.each(f,function(a,f){(d=B[a])&&(e=d._nr,c(d,f),H(d,e,!0),b._isVisible&&(g&&d._$3d.html(k(d,e,d._first+i-m+1,d._last-i+m,!0)),d._$scroller.html(k(d,e,d._first,d._last)).css("margin-top",d._margin+"px"),d._refresh(q)))}),!b._isVisible||b._isLiquid||q||b.position(),q||w(h,void 0,void 0,j)},b.getValidValue=N,b._generateContent=function(){var l,n=0,j="",o=g?y+"transform: translateZ("+(e*d.rows/2+3)+"px);":"",q='<div class="mbsc-sc-whl-l" style="'+o+"height:"+e+"px;margin-top:-"+(e/2+(d.selectedLineBorder||0))+'px;"></div>',f=0;return a.each(d.wheels,function(s,r){j+='<div class="mbsc-w-p mbsc-sc-whl-gr-c'+(g?" mbsc-sc-whl-gr-3d-c":"")+(d.showLabel?" mbsc-sc-lbl-v":"")+'">'+q+'<div class="mbsc-sc-whl-gr'+(g?" mbsc-sc-whl-gr-3d":"")+(p?" mbsc-sc-cp":"")+(d.width||d.maxWidth?'"':'" style="max-width:'+d.maxPopupWidth+'px;"')+">",a.each(r,function(r,a){b._tempSelected[f]=c({},b._selected[f]),h[f]=H(a,f),n+=d.maxWidth?d.maxWidth[f]||d.maxWidth:d.width?d.width[f]||d.width:0,l=void 0!==a.label?a.label:r,j+='<div class="mbsc-sc-whl-w '+(a.cssClass||"")+(a.multiple?" mbsc-sc-whl-multi":"")+'" style="'+(d.width?"width:"+(d.width[f]||d.width)+"px;":(d.minWidth?"min-width:"+(d.minWidth[f]||d.minWidth)+"px;":"")+(d.maxWidth?"max-width:"+(d.maxWidth[f]||d.maxWidth)+"px;":""))+'">'+(F?'<div class="mbsc-sc-bar-c"><div class="mbsc-sc-bar"></div></div>':"")+'<div class="mbsc-sc-whl-o" style="'+o+'"></div>'+q+'<div aria-live="off" aria-label="'+l+'"'+(a.multiple?' aria-multiselectable="true"':"")+' role="listbox" data-index="'+f+'" class="mbsc-sc-whl" style="height:'+d.rows*e*(g?1.1:1)+'px;">'+(p?'<div data-index="'+f+'" data-step="1" class="mbsc-sc-btn mbsc-sc-btn-plus '+(d.btnPlusClass||"")+'"></div><div data-index="'+f+'" data-step="-1" class="mbsc-sc-btn mbsc-sc-btn-minus '+(d.btnMinusClass||"")+'"></div>':"")+'<div class="mbsc-sc-lbl">'+l+'</div><div class="mbsc-sc-whl-c" style="height:'+x+"px;margin-top:-"+(x/2+1)+"px;"+o+'"><div class="mbsc-sc-whl-sc" style="top:'+(x-e)/2+'px;">',j+=k(a,f,a._first,a._last)+"</div></div>",g&&(j+='<div class="mbsc-sc-whl-3d" style="height:'+e+"px;margin-top:-"+e/2+'px;">',j+=k(a,f,a._first+i-m+1,a._last-i+m,!0),j+="</div>"),j+="</div></div>",f++}),j+="</div></div>"}),n&&(d.maxPopupWidth=n),j},b._attachEvents=function(b){v=aO(a(".mbsc-sc-btn",b),S,d.delay,O,!0),a(".mbsc-sc-whl",b).on("keydown",T).on("keyup",U)},b._detachEvents=function(){v.stop();for(var a=0;a<h.length;a++)h[a]._scroller.destroy()},b._markupReady=function(b){a(".mbsc-sc-whl-w",L=b).each(function(c){var l,f=a(this),b=h[c];b._$markup=f,b._$scroller=a(".mbsc-sc-whl-sc",this),b._$3d=a(".mbsc-sc-whl-3d",this),b._scroller=new X(this,{mousewheel:d.mousewheel,moveElement:b._$scroller,scrollbar:a(".mbsc-sc-bar-c",this),initialPos:(b._first-b._index)*e,contSize:d.rows*e,snap:e,minScroll:J(b),maxScroll:P(b),maxSnapScroll:i,prevDef:!0,stopProp:!0,timeUnit:3,easing:"cubic-bezier(0.190, 1.000, 0.220, 1.000)",sync:function(c,a,d){var f=a?y+"transform "+Math.round(a)+"ms "+d:"";g&&(b._$3d[0].style[n+"Transition"]=f,b._$3d[0].style[n+"Transform"]="rotateX("+-c/e*E+"deg)")},onStart:function(b,a){a.settings.readonly=O(c)},onGestureStart:function(){f.addClass("mbsc-sc-whl-a mbsc-sc-whl-anim"),j("onWheelGestureStart",{index:c})},onGestureEnd:function(a){var d=90==a.direction?1:2,f=a.duration,g=a.destinationY;l=Math.round(-g/e)+b._offset,C(b,c,l,f,d)},onAnimationStart:function(){f.addClass("mbsc-sc-whl-anim")},onAnimationEnd:function(){f.removeClass("mbsc-sc-whl-a mbsc-sc-whl-anim"),j("onWheelAnimationEnd",{index:c}),b._$3d.find(".mbsc-sc-itm-del").remove()},onMove:function(d){!function(b,l,o){var n=Math.round(-o/e)+b._offset,c=n-b._current,d=b._first,f=b._last,h=d+i-m+1,j=f-i+m;c&&(b._first+=c,b._last+=c,b._current=n,0<c?(b._$scroller.append(k(b,l,Math.max(f+1,d+c),f+c)),a(".mbsc-sc-itm",b._$scroller).slice(0,Math.min(c,f-d+1)).remove(),g&&(b._$3d.append(k(b,l,Math.max(j+1,h+c),j+c,!0)),a(".mbsc-sc-itm",b._$3d).slice(0,Math.min(c,j-h+1)).attr("class","mbsc-sc-itm-del"))):c<0&&(b._$scroller.prepend(k(b,l,d+c,Math.min(d-1,f+c))),a(".mbsc-sc-itm",b._$scroller).slice(Math.max(c,d-f-1)).remove(),g&&(b._$3d.prepend(k(b,l,h+c,Math.min(h-1,j+c),!0)),a(".mbsc-sc-itm",b._$3d).slice(Math.max(c,h-j-1)).attr("class","mbsc-sc-itm-del"))),b._margin+=c*e,b._$scroller.css("margin-top",b._margin+"px"))}(b,c,d.posY)},onBtnTap:function(b){Q(c,a(b.target))}})}),w()},b._fillValue=function(){A(b._hasValue=!0,!0,0,!0)},b._clearValue=function(){a(".mbsc-sc-whl-multi .mbsc-sc-itm-sel",L).removeClass(l).removeAttr("aria-selected")},b._readValue=function(){var g=D.val()||"",e=0;""!==g&&(b._hasValue=!0),b._tempWheelArray=f=b._hasValue&&b._wheelArray?b._wheelArray.slice(0):d.parseValue.call(o,g,b)||[],b._tempSelected=c(!0,{},b._selected),a.each(d.wheels,function(c,b){a.each(b,function(b,a){h[e]=H(a,e),e++})}),A(!1,!1,0,!0),j("onRead")},b.__processSettings=function(a){d=b.settings,j=b.trigger,t=d.multiline,l="mbsc-sc-itm-sel mbsc-ic mbsc-ic-"+d.checkIcon,(F=!d.touchUi)&&(d.tapSelect=!0,d.circular=!1,d.rows=a.rows||I.rows||7)},b.__init=function(a){a&&(b._wheelArray=null),h=[],B={},p=d.showScrollArrows,g=d.scroll3d&&cR&&!p&&!F&&("ios"==d.theme||"ios"==d.baseTheme),e=d.height,x=g?2*Math.round((e-.03*(e*d.rows/2+3))/2):e,m=Math.round(1.8*d.rows),E=360/(2*m),p&&(d.rows=Math.max(3,d.rows))},b._getItemValue=u,b._tempSelected={},b._selected={},R||b.init()}k.prototype={_hasDef:!0,_hasTheme:!0,_hasLang:!0,_responsive:!0,_class:"scroller",_presets:i,_defaults:c({},z.prototype._defaults,{minWidth:80,height:40,rows:3,multiline:1,delay:200,readonly:!1,showLabel:!0,setOnTap:!1,wheels:[],preset:"",speedUnit:.0012,timeUnit:.08,checkIcon:"checkmark",compClass:"mbsc-sc",validate:function(){},formatValue:function(a){return a.join(" ")},parseValue:function(e,b){var c,d,f=[],g=[],h=0;return null!=e&&(f=(e+"").split(" ")),a.each(b.settings.wheels,function(i,e){a.each(e,function(i,e){d=e.data,c=b._getItemValue(d[0]),a.each(d,function(d,a){if(f[h]==b._getItemValue(a))return c=b._getItemValue(a),!1}),g.push(c),h++})}),g}})},l.Scroller=k;function bc(e){function _(d){var e,c,a,h,f=[];if(d){for(e=0;e<d.length;e++)if((c=d[e]).start&&c.end&&!bn.test(c.start))for(a=new Date(g(c.start,n,b)),h=new Date(g(c.end,n,b));a<=h;)f.push(O(a.getFullYear(),a.getMonth(),a.getDate())),a.setDate(a.getDate()+1);else f.push(c);return f}return d}function l(b,a,c,d){return Math.min(d,Math.floor(b/a)*a+c)}function L(a,b,c){return Math.floor((c-b)/a)*a+b}function H(a){return a.getFullYear()+"-"+u(a.getMonth()+1)+"-"+u(a.getDate())}function p(e,a,c,f){var b;return void 0===d[a]||(b=+e[d[a]],isNaN(b))?c?i[a](c):void 0!==I[a]?I[a]:i[a](f):b}function r(c){var a,e=new Date((new Date).setHours(0,0,0,0)),f,g,i,h;return null===c?c:(void 0!==d.dd&&(a=c[d.dd].split("-"),a=new Date(a[0],a[1]-1,a[2])),void 0!==d.tt&&(a=a||e,a=new Date(a.getTime()+c[d.tt]%86400*1e3)),f=p(c,"y",a,e),g=p(c,"m",a,e),i=Math.min(p(c,"d",a,e),b.getMaxDayOfMonth(f,g)),h=p(c,"h",a,e),b.getDate(f,g,i,v&&p(c,"a",a,e)?h+12:h,p(c,"i",a,e),p(c,"s",a,e),p(c,"u",a,e)))}function D(b,g){var c,a,e=["y","m","d","a","h","i","s","u","dd","tt"],f=[];if(null==b)return b;for(c=0;c<e.length;c++)void 0!==d[a=e[c]]&&(f[d[a]]=i[a](b)),g&&(I[a]=i[a](b));return f}function N(a,b){return b?Math.floor(new Date(a)/864e5):a.getMonth()+12*(a.getFullYear()-1970)}function aj(a){return{value:a,display:(/yy/i.test(y)?a:(a+"").substr(2,2))+(b.yearSuffix||"")}}function ak(a){return a}function al(c){var a=/d/i.test(c);return{label:"",cssClass:"mbsc-dt-whl-date",min:f?N(H(f),a):void 0,max:k?N(H(k),a):void 0,data:function(e){var f=new Date((new Date).setHours(0,0,0,0)),d=a?new Date(864e5*e):new Date(1970,e,1);return a&&(d=new Date(d.getUTCFullYear(),d.getUTCMonth(),d.getUTCDate())),{invalid:a&&!x(d,!0),value:H(d),display:f.getTime()==d.getTime()?b.todayText:j(c,d,b)}},getIndex:function(b){return N(b,a)}}}function am(d){var a,c,e,f=[];for(/s/i.test(d)?c=z:/i/i.test(d)?c=60*s:/h/i.test(d)&&(c=3600*w),G=m.tt=c,a=0;a<86400;a+=c)e=new Date((new Date).setHours(0,0,0,0)+1e3*a),f.push({value:a,display:j(d,e,b)});return{label:"",cssClass:"mbsc-dt-whl-time",data:f}}function ab(a,c){return b.getYear(a)===b.getYear(c)&&b.getMonth(a)===b.getMonth(c)}function x(a,b){return!(!b&&a<f)&&!(!b&&k<a)&&(!!aa(a,S)||!aa(a,R))}function aa(c,f){var d,e,a;if(f)for(e=0;e<f.length;e++)if(a=(d=f[e])+"",!d.start)if(A.test(a)){if((a=+a.replace("w",""))==c.getDay())return!0}else if(M.test(a)){if((a=a.split("/"))[1]){if(a[0]-1==c.getMonth()&&a[1]==c.getDate())return!0}else if(a[0]==c.getDate())return!0}else if(d=g(d,n,b),c.getFullYear()==d.getFullYear()&&c.getMonth()==d.getMonth()&&c.getDate()==d.getDate())return!0;return!1}function Z(i,k,j,l,m,h,e){var c,f,d,a;if(i)for(f=0;f<i.length;f++)if(a=(c=i[f])+"",!c.start)if(A.test(a))for(d=(a=+a.replace("w",""))-l;d<m;d+=7)0<=d&&(h[d+1]=e);else M.test(a)?(a=a.split("/"))[1]?a[0]-1==j&&(h[a[1]]=e):h[a[0]]=e:(c=g(c,n,b),b.getYear(c)==k&&b.getMonth(c)==j&&(h[b.getDay(c)]=e))}function X(u,y,e,L,I,z,N,M){var F,B,p,H,E,w,g,D,x,a,f,c,d,s,j,J,C,k,n,r,K={},i=b.getDate(L,I,z),h=["a","h","i","s"];if(u){for(g=0;g<u.length;g++)(f=u[g]).start&&(f.apply=!1,k=(C=(p=f.d)+"").split("/"),p&&(p.getTime&&L==b.getYear(p)&&I==b.getMonth(p)&&z==b.getDay(p)||!A.test(C)&&(k[1]&&z==k[1]&&I==k[0]-1||!k[1]&&z==k[0])||A.test(C)&&i.getDay()==+C.replace("w",""))&&(f.apply=!0,K[i]=!0));for(g=0;g<u.length;g++)if(f=u[g],J=F=0,D=t[e],x=o[e],B=!(j=s=!0),f.start&&(f.apply||!f.d&&!K[i])){for(c=f.start.split(":"),d=f.end.split(":"),a=0;a<3;a++)void 0===c[a]&&(c[a]=0),void 0===d[a]&&(d[a]=59),c[a]=+c[a],d[a]=+d[a];if("tt"==e)D=l(Math.round((new Date(i).setHours(c[0],c[1],c[2])-new Date(i).setHours(0,0,0,0))/1e3),G,0,86400),x=l(Math.round((new Date(i).setHours(d[0],d[1],d[2])-new Date(i).setHours(0,0,0,0))/1e3),G,0,86400);else{for(c.unshift(11<c[0]?1:0),d.unshift(11<d[0]?1:0),v&&(12<=c[1]&&(c[1]=c[1]-12),12<=d[1]&&(d[1]=d[1]-12)),a=0;a<y;a++)void 0!==q[a]&&(n=l(c[a],m[h[a]],t[h[a]],o[h[a]]),r=l(d[a],m[h[a]],t[h[a]],o[h[a]]),w=E=H=0,v&&1==a&&(H=c[0]?12:0,E=d[0]?12:0,w=q[0]?12:0),s||(n=0),j||(r=o[h[a]]),(s||j)&&n+H<q[a]+w&&q[a]+w<r+E&&(B=!0),q[a]!=n&&(s=!1),q[a]!=r&&(j=!1));if(!M)for(a=y+1;a<4;a++)0<c[a]&&(F=m[e]),d[a]<o[h[a]]&&(J=m[e]);B||(n=l(c[y],m[e],t[e],o[e])+F,r=l(d[y],m[e],t[e],o[e])-J,s&&(D=n),j&&(x=r))}if(s||j||B)for(a=D;a<=x;a+=m[e])N[a]=!M}}}var G,W,$,d={},I={},h={},q=[],ae=function(c){var a,d,e,b={};if(c.is("input")){switch(c.attr("type")){case"date":a="yy-mm-dd";break;case"datetime":a="yy-mm-ddTHH:ii:ssZ";break;case"datetime-local":a="yy-mm-ddTHH:ii:ss";break;case"month":a="yy-mm",b.dateOrder="mmyy";break;case"time":a="HH:ii:ss"}b.format=a,d=c.attr("min"),e=c.attr("max"),d&&"undefined"!=d&&(b.min=V(a,d)),e&&"undefined"!=e&&(b.max=V(a,e))}return b}(a(this)),ac=c({},e.settings),ao=aV[ac.calendarSystem],b=c(e.settings,az,ao,cN,ae,ac),B=b.preset,K="datetime"==B?b.dateFormat+b.separator+b.timeFormat:"time"==B?b.timeFormat:b.dateFormat,n=ae.format||K,af=b.dateWheels||b.dateFormat,P=b.timeWheels||b.timeFormat,y=b.dateWheels||b.dateDisplay,E=P,ai=b.baseTheme||b.theme,R=_(b.invalid),S=_(b.valid),f=g(b.min,n,b),k=g(b.max,n,b),Y=/time/i.test(B),v=/h/.test(E),an=/D/.test(y),J=b.steps||{},w=J.hour||b.stepHour||1,s=J.minute||b.stepMinute||1,z=J.second||b.stepSecond||1,Q=J.zeroBased,F=Q||!f?0:f.getHours()%w,C=Q||!f?0:f.getMinutes()%s,T=Q||!f?0:f.getSeconds()%z,U=L(w,F,v?11:23),ah=L(s,C,59),ag=L(s,C,59),t={y:f?f.getFullYear():-1/0,m:0,d:1,h:F,i:C,s:T,a:0,tt:0},o={y:k?k.getFullYear():1/0,m:11,d:31,h:U,i:ah,s:ag,a:1,tt:86400},m={y:1,m:1,d:1,h:w,i:s,s:z,a:1,tt:1},i={y:function(a){return b.getYear(a)},m:function(a){return b.getMonth(a)},d:function(a){return b.getDay(a)},h:function(b){var a=b.getHours();return l(a=v&&12<=a?a-12:a,w,F,U)},i:function(a){return l(a.getMinutes(),s,C,ah)},s:function(a){return l(a.getSeconds(),z,T,ag)},u:function(a){return a.getMilliseconds()},a:function(a){return 11<a.getHours()?1:0},dd:H,tt:function(a){return l(Math.round((a.getTime()-new Date(a).setHours(0,0,0,0))/1e3),G||1,0,86400)}};return e.getVal=function(a){return e._hasValue||a?ad(r(e.getArrayVal(a)),b,n):null},e.getDate=function(a){return e._hasValue||a?r(e.getArrayVal(a)):null},e.setDate=function(a,b,c,d,f){e.setArrayVal(D(a,!0),b,f,d,c)},$=function(){var o,p,c,j,a,i,g,n,e=0,q=[],m=[],l=[];if(/date/i.test(B)){for(o=af.split(/\|/.test(af)?"|":""),j=0;j<o.length;j++)if(i=0,(c=o[j]).length)if(/y/i.test(c)&&(h.y=1,i++),/m/i.test(c)&&(h.y=1,h.m=1,i++),/d/i.test(c)&&(h.y=1,h.m=1,h.d=1,i++),1<i&&void 0===d.dd)d.dd=e,e++,m.push(al(c)),l=m,W=!0;else if(/y/i.test(c)&&void 0===d.y)d.y=e,e++,m.push({cssClass:"mbsc-dt-whl-y",label:b.yearText,min:f?b.getYear(f):void 0,max:k?b.getYear(k):void 0,data:aj,getIndex:ak});else if(/m/i.test(c)&&void 0===d.m){for(d.m=e,g=[],e++,a=0;a<12;a++)n=y.replace(/[dy|]/gi,"").replace(/mm/,u(a+1)+(b.monthSuffix||"")).replace(/m/,a+1+(b.monthSuffix||"")),g.push({value:a,display:/MM/.test(n)?n.replace(/MM/,'<span class="mbsc-dt-month">'+b.monthNames[a]+"</span>"):n.replace(/M/,'<span class="mbsc-dt-month">'+b.monthNamesShort[a]+"</span>")});m.push({cssClass:"mbsc-dt-whl-m",label:b.monthText,data:g})}else if(/d/i.test(c)&&void 0===d.d){for(d.d=e,g=[],e++,a=1;a<32;a++)g.push({value:a,display:(/dd/i.test(y)?u(a):a)+(b.daySuffix||"")});m.push({cssClass:"mbsc-dt-whl-d",label:b.dayText,data:g})}q.push(m)}if(/time/i.test(B)){for(p=P.split(/\|/.test(P)?"|":""),j=0;j<p.length;j++)if(i=0,(c=p[j]).length&&(/h/i.test(c)&&(h.h=1,i++),/i/i.test(c)&&(h.i=1,i++),/s/i.test(c)&&(h.s=1,i++),/a/i.test(c)&&i++),1<i&&void 0===d.tt)d.tt=e,e++,l.push(am(c));else if(/h/i.test(c)&&void 0===d.h){for(g=[],d.h=e,h.h=1,e++,a=F;a<(v?12:24);a+=w)g.push({value:a,display:v&&0===a?12:/hh/i.test(E)?u(a):a});l.push({cssClass:"mbsc-dt-whl-h",label:b.hourText,data:g})}else if(/i/i.test(c)&&void 0===d.i){for(g=[],d.i=e,h.i=1,e++,a=C;a<60;a+=s)g.push({value:a,display:/ii/i.test(E)?u(a):a});l.push({cssClass:"mbsc-dt-whl-i",label:b.minuteText,data:g})}else if(/s/i.test(c)&&void 0===d.s){for(g=[],d.s=e,h.s=1,e++,a=T;a<60;a+=z)g.push({value:a,display:/ss/i.test(E)?u(a):a});l.push({cssClass:"mbsc-dt-whl-s",label:b.secText,data:g})}else/a/i.test(c)&&void 0===d.a&&(d.a=e,e++,l.push({cssClass:"mbsc-dt-whl-a",label:b.ampmText,data:/A/.test(c)?[{value:0,display:b.amText.toUpperCase()},{value:1,display:b.pmText.toUpperCase()}]:[{value:0,display:b.amText},{value:1,display:b.pmText}]}));l!=m&&q.push(l)}return q}(),b.isoParts=h,e._format=K,e._order=d,e.handlers.now=function(){e.setDate(new Date,e.live,1e3,!0,!0)},e.buttons.now={text:b.nowText,icon:b.nowIcon,handler:"now"},{minWidth:W&&Y?{bootstrap:46,ios:50,material:46,mobiscroll:46,windows:50}[ai]:void 0,compClass:"mbsc-dt mbsc-sc",wheels:$,headerText:!!b.headerText&&function(){return j(K,r(e.getArrayVal(!0)),b)},formatValue:function(a){return j(n,r(a),b)},parseValue:function(a){return a||(I={},e._hasValue=!1),D(g(a||b.defaultValue||new Date,n,b,h),!!a)},validate:function(A){var c,j,u,w,G=A.values,v=A.index,F=A.direction,s=b.wheels[0][d.d],l=function(a,e){var b,c,g=!1,d=!1,h=0,i=0,j=f?r(D(f)):-1/0,l=k?r(D(k)):1/0;if(x(a))return a;if(a<j&&(a=j),l<a&&(a=l),c=b=a,2!==e)for(g=x(b,!0);!g&&b<l&&h<100;)g=x(b=new Date(b.getTime()+864e5),!0),h++;if(1!==e)for(d=x(c,!0);!d&&j<c&&i<100;)d=x(c=new Date(c.getTime()-864e5),!0),i++;return 1===e&&g?b:2===e&&d?c:ab(a,b)?b:ab(a,c)?c:i<=h&&d?c:b}(r(G),F),E=D(l),n=[],C={},g=i.y(l),h=i.m(l),p=b.getMaxDayOfMonth(g,h),z=!0,B=!0;if(a.each(["dd","y","m","d","tt","a","h","i","s"],function(v,c){var q=t[c],r=o[c],e=i[c](l),u,s;if(n[d[c]]=[],z&&f&&(q=i[c](f)),B&&k&&(r=i[c](k)),e<q&&(e=q),r<e&&(e=r),"dd"!==c&&"tt"!==c&&(z=z&&e==q,B=B&&e==r),void 0!==d[c]){if("y"!=c&&"dd"!=c)for(j=t[c];j<=o[c];j+=m[c])(j<q||r<j)&&n[d[c]].push(j);"d"==c&&(u=b.getDate(g,h,1).getDay(),s={},Z(R,g,h,u,p,s,1),Z(S,g,h,u,p,s,0),a.each(s,function(a,b){b&&n[d[c]].push(a)}))}}),Y&&a.each(["a","h","i","s","tt"],function(f,b){var k=i[b](l),j=i.d(l),c={};void 0!==d[b]&&(X(R,f,b,g,h,j,c,0),X(S,f,b,g,h,j,c,1),a.each(c,function(a,c){c&&n[d[b]].push(a)}),q[f]=e.getValidValue(d[b],k,F,c))}),s&&(s._length!==p||an&&(void 0===v||v===d.y||v===d.m))){for((C[d.d]=s).data=[],c=1;c<=p;c++)w=b.getDate(g,h,c).getDay(),u=y.replace(/[my|]/gi,"").replace(/dd/,(c<10?"0"+c:c)+(b.daySuffix||"")).replace(/d/,c+(b.daySuffix||"")),s.data.push({value:c,display:/DD/.test(u)?u.replace(/DD/,'<span class="mbsc-dt-day">'+b.dayNames[w]+"</span>"):u.replace(/D/,'<span class="mbsc-dt-day">'+b.dayNamesShort[w]+"</span>")});e._tempWheelArray[d.d]=E[d.d],e.changeWheel(C)}return{disabled:n,valid:E}}}}function bj(e){var B,i,Y,Z,au,L,aG,_,y,aM,h,an,ad,bd,l,R,aK,G,aj,k,N,ai,f,E,s,n,S,aB,U,I,a_,ab,C,p,T,o,aF,ax,aw,v,aQ,al,aU,ac,r,aY,a$,af,ag,ah,J,az,ak,z,j,q,aC,w,b,at,m,ba,u,W,aL,D,ay,K,ao,ae,aE,aZ,bk=1,bj=this;function F(a){a.hasClass("mbsc-cal-h")||a.addClass("mbsc-cal-h")}function aX(a){a.hasClass("mbsc-cal-h")?function(a){a.hasClass("mbsc-cal-h")&&(a.removeClass("mbsc-cal-h"),e._onSelectShow())}(a):F(a)}function aa(a,b,c){a[b]=a[b]||[],a[b].push(c)}function V(k,f,h){var i,d,j,c,l=b.getYear(f),m=b.getMonth(f),e={};return k&&a.each(k,function(k,a){if(i=a.d||a.start||a,d=i+"",a.start&&a.end)for(c=H(g(a.start,E,b)),j=H(g(a.end,E,b));c<=j;)aa(e,c,a),c=b.getDate(b.getYear(c),b.getMonth(c),b.getDay(c)+1);else if(A.test(d))for(c=$(f,!1,+d.replace("w",""));c<=h;)aa(e,c,a),c=b.getDate(b.getYear(c),b.getMonth(c),b.getDay(c)+7);else if(M.test(d))if((d=d.split("/"))[1])for(c=b.getDate(l,d[0]-1,d[1]);c<=h;)aa(e,c,a),c=b.getDate(b.getYear(c)+1,b.getMonth(c),d[1]);else for(c=b.getDate(l,m,d[0]);c<=h;)aa(e,c,a),c=b.getDate(b.getYear(c),b.getMonth(c)+1,d[0]);else aa(e,H(g(i,E,b)),a)}),e}function bi(b){var a,h,g,i,d=!!ax[b]&&ax[b],f=!!aw[b]&&aw[b],k=f&&f[0].background?f[0].background:d&&d[0].background,j="";if(f)for(a=0;a<f.length;a++)j+=(f[a].cssClass||"")+" ";if(d){for(g='<div class="mbsc-cal-marks">',a=0;a<d.length;a++)j+=((h=d[a]).cssClass||"")+" ",g+='<div class="mbsc-cal-mark"'+(h.color?' style="background:'+h.color+';"':"")+"></div>";g+="</div>"}return i={marked:d,background:k,cssClass:j,markup:N[b]?N[b].join(""):aB?g:""},c(i,e._getDayProps(b,i))}function aA(a){return' style="'+(o?"transform: translateY("+100*a+"%)":"left:"+100*a*w+"%")+'"'}function aP(a){return x(a,j-1)>v&&(a=x(v,1-j)),a<r&&(a=r),a}function am(b,f,d){var c=b.color,e=b.text;return'<div data-id="'+b._id+'" data-index="'+f+'" class="mbsc-cal-txt" title="'+a("<div>"+e+"</div>").text()+'"'+(c?' style="background:'+c+(d?";color:"+dg(c):"")+';"':"")+">"+(d?e:"")+"</div>"}function aR(d){var c=$(x(d,-q-z),!1),a=$(x(d,-q+j+z-1),!1);a=b.getDate(b.getYear(a),b.getMonth(a),b.getDay(a)+7*h),e._onGenMonth(c,a),a_=V(b.invalid,c,a),ba=V(b.valid,c,a),ax=V(b.labels||b.events||b.marked,c,a),aw=V(b.colors,c,a),aF=e._labels||ax||aw,(S=b.labels||e._labels)&&function(){N={};for(var e={},d=c,f=function(){var v,q,i,j,f,m,a,k,h,u,o,w,c,r,l,p,x,n;d.getDay()==s&&(e={});for(v=al,q=aF[d]||[],i=q.length,j=[],f=void 0,m=void 0,a=0,k=0,h=0,u=void 0;a<v;)(f=null,q.forEach(function(b,c){e[a]==b&&(f=b,m=c)}),a==v-1&&(k<i-1||i&&h==i&&!f))?(o=i-k,w=(1<o&&b.moreEventsPluralText||b.moreEventsText).replace(/{count}/,o),o&&j.push('<div class="mbsc-cal-txt-more">'+w+"</div>"),f&&(e[a]=null,f._days.forEach(function(c){N[c][a]='<div class="mbsc-cal-txt-more">'+b.moreEventsText.replace(/{count}/,1)+"</div>"})),k++,a++):f?(m==h&&h++,t(d,g(f.end))&&(e[a]=null),j.push(am(f,m)),a++,k++,f._days.push(d)):h<i?(c=q[h],r=c.start&&g(c.start),l=c.end&&g(c.end),p=d.getDay(),x=0<s-p?7:0,n=l&&!t(r,l),r&&!t(d,r)&&p!=s||(void 0===c._id&&(c._id=bk++),n&&(e[a]=c),c._days=[d],u=n?100*Math.min(cH(d,H(l))+1,7+s-p-x):100,j.push(n?'<div class="mbsc-cal-txt-w" style="width:'+u+'%">'+am(c,h,!0)+"</div>"+am(c,h):am(c,h,!0)),a++,k++),h++):(j.push('<div class="mbsc-cal-txt-ph"></div>'),a++);N[d]=j,d=b.getDate(b.getYear(d),b.getMonth(d),b.getDay(d)+1)};d<a;)f()}()}function bh(a){var c=b.getYear(a),d=b.getMonth(a);aD(y=k=a),m("onMonthChange",{year:c,month:d}),m("onMonthLoading",{year:c,month:d}),m("onPageChange",{firstDay:a}),m("onPageLoading",{firstDay:a}),aR(a)}function aT(a){var c=b.getYear(a),d=b.getMonth(a);void 0===ak?be(a,c,d):aJ(a,ak,!0),aI(y,f.focus),f.focus=!1}function be(c,d,e){var b=f.$scroller;a(".mbsc-cal-slide",b).removeClass("mbsc-cal-slide-a"),a(".mbsc-cal-slide",b).slice(z,z+j).addClass("mbsc-cal-slide-a"),S&&a(".mbsc-cal-slide-a .mbsc-cal-txt",b).on("mouseenter",function(){var c=a(this).attr("data-id");a('.mbsc-cal-txt[data-id="'+c+'"]',b).addClass("mbsc-hover")}).on("mouseleave",function(){a(".mbsc-cal-txt.mbsc-hover",b).removeClass("mbsc-hover")}),m("onMonthLoaded",{year:d,month:e}),m("onPageLoaded",{firstDay:c})}function aV(e,f){var c,a=b.getYear(e),d='<div class="mbsc-cal-slide"'+aA(f)+'><div role="grid" class="mbsc-cal-table"><div class="mbsc-cal-row">';for(c=0;c<12;c++)c&&c%3==0&&(d+='</div><div class="mbsc-cal-row">'),d+='<div role="gridcell" tabindex="-1" aria-label="'+a+'" data-val="'+a+'" class="mbsc-cal-cell mbsc-btn-e '+(a<af||ac<a?" mbsc-disabled ":"")+(a==b.getYear(k)?G:"")+'"><div class="mbsc-cal-cell-i mbsc-cal-cell-txt">'+a+ao+"</div></div>",a++;return d+="</div></div></div>"}function aW(B,I){var d,p,n,m,k,w,g,z,a,q,u,A,o,C,y,f,j,i=1,H=b.getYear(B),x=b.getMonth(B),D=b.getDay(B),E=null!==b.defaultValue||e._hasValue?e.getDate(!0):null,F=b.getDate(H,x,D).getDay(),J=0<s-F?7:0,l='<div class="mbsc-cal-slide"'+aA(I)+'><div role="grid" class="mbsc-cal-table"><div class="mbsc-cal-row">';for(f=0;f<7*h;f++)y=f+s-J,n=(d=b.getDate(H,x,y-F+D)).getFullYear(),m=d.getMonth(),k=d.getDate(),w=b.getMonth(d),g=b.getDay(d),C=b.getMaxDayOfMonth(n,m),z=n+"-"+(m+1)+"-"+k,q=(a=c({valid:(j=d,!(j<r||v<j||void 0!==a_[j]&&void 0===ba[j])),selected:E&&t(E,d)},bi(d))).valid,u=a.selected,p=a.cssClass,A=new Date(d).setHours(12,0,0,0)===(new Date).setHours(12,0,0,0),o=w!==x,ai[z]=a,f&&f%7==0&&(l+='</div><div class="mbsc-cal-row">'),W&&f%7==0&&("month"==W&&o&&1<i?i=1==k?1:2:"year"==W&&(i=b.getWeekNumber(b.getDate(n,m,k+(7-s+1)%7))),l+='<div role="gridcell" class="mbsc-cal-cell mbsc-cal-week-nr">'+i+"</div>",i++),l+='<div role="gridcell" aria-label="'+(A?b.todayText+", ":"")+b.dayNames[d.getDay()]+", "+b.monthNames[w]+" "+g+" "+(a.ariaLabel?", "+a.ariaLabel:"")+'"'+(o&&!at?' aria-hidden="true"':' data-full="'+z+'"')+(u?' aria-selected="true"':"")+(q?' tabindex="-1"':' aria-disabled="true"')+' class="mbsc-cal-cell mbsc-cal-day mbsc-cal-day'+y%7+" "+(b.dayClass||"")+" "+(u?G:"")+(A?" "+b.todayClass:"")+(p?" "+p:"")+(1==g?" mbsc-cal-day-first":"")+(g==C?" mbsc-cal-day-last":"")+(o?" mbsc-cal-day-diff":"")+(q?" mbsc-btn-e":" mbsc-disabled")+(a.marked?" mbsc-cal-day-marked":"")+(a.background?" mbsc-cal-day-colored":"")+'"><div class="mbsc-cal-cell-i mbsc-cal-day-i"><div class="mbsc-cal-day-date mbsc-cal-cell-txt"'+(a.background?' style="background:'+a.background+";color:"+dg(a.background)+'"':"")+">"+g+"</div>"+(a.markup?"<div>"+a.markup+"</div>":"")+"</div></div>";return l+="</div></div></div>"}function aJ(a,k,i){var c,e=b.getYear(a),g=b.getMonth(a),j=f?f.pos:0,d="";if(ai={},h)for(k||(m("onMonthLoading",{year:e,month:g}),m("onPageLoading",{firstDay:a})),aR(a),c=0;c<aC;c++)d+=aW(x(a,c-q-z),j*w+c-z);return ak=void 0,i&&f&&(f.$active=null,f.$scroller.html(d),be(a,e,g)),d}function aI(c,d){if(f){var b=f.$active;b&&b.length&&(b[0].blur(),b.hasClass("mbsc-disabled")?b.removeAttr("tabindex"):b.attr("tabindex","-1")),f.$active=a('.mbsc-cal-slide-a .mbsc-cal-day[data-full="'+ap(c)+'"]',f.$scroller).attr("tabindex","0"),d&&f.$active.length&&f.$active[0].focus()}}function bf(c,b){a(".mbsc-selected",b).removeClass(G).removeAttr("aria-selected"),a('.mbsc-cal-cell[data-val="'+c+'"]',b).addClass(G).attr("aria-selected","true")}function P(c,i,l,m){var d,g;u&&(c<r&&(c=r),v<c&&(c=v),"calendar"!==u&&U&&!i||(e._isSetDate=!i,n&&h&&(g=$(aP(c),p),az&&(c<x(k,-q)||c>=x(k,j-q))&&(d=p?b.getMonth(g)-b.getMonth(k)+12*(b.getYear(g)-b.getYear(k)):Math.floor(cH(k,g)/(7*h)))&&(f.queue=[],f.focus=m&&l,aq(f,d,l)),d&&l||aI(c,m),i||function(d){var c=f&&f.$scroller;b.highlight&&f&&(a(".mbsc-selected",c).removeClass(G).removeAttr("aria-selected"),null===b.defaultValue&&!e._hasValue||a('.mbsc-cal-day[data-full="'+ap(d)+'"]',c).addClass(G).attr("aria-selected","true"))}(c),p||aD(c,!0),y=c,az=!0),e._onSetDate(c,d),e._isSetDate=!1))}function aD(e,l){var c,k,h,d=b.getYear(e),g=b.getMonth(e),f=d+ao;if(I){if(bf(g,J.$scroller),bf(d,K.$scroller),aq(K,Math.floor(d/12)-Math.floor(b.getYear(K.first)/12),!0),a(".mbsc-cal-cell",J.$scroller).removeClass("mbsc-disabled"),d===af)for(c=0;c<a$;c++)a('.mbsc-cal-cell[data-val="'+c+'"]',J.$scroller).addClass("mbsc-disabled");if(d===ac)for(c=aU+1;c<=12;c++)a('.mbsc-cal-cell[data-val="'+c+'"]',J.$scroller).addClass("mbsc-disabled")}for(l||(av(a(".mbsc-cal-prev-m",i),x(e,-q)<=r),av(a(".mbsc-cal-next-m",i),x(e,j-q)>v),av(a(".mbsc-cal-prev-y",i),b.getDate(d-1,g+1,1)<=r),av(a(".mbsc-cal-next-y",i),b.getDate(d+1,g,1)>v)),aG.attr("aria-label",d).html(f),c=0;c<j;c++)e=b.getDate(d,g-q+c,1),k=b.getYear(e),h=b.getMonth(e),f=k+ao,Y.eq(c).attr("aria-label",b.monthNames[h]+(D?"":" "+d)).html((!D&&ay<ag?f+" ":"")+ah[h]+(!D&&ag<ay?" "+f:""))}function av(a,b){b?a.addClass(aK).attr("aria-disabled","true"):a.removeClass(aK).removeAttr("aria-disabled")}function bb(f,p){var k=e.getDate(!0),h=f[0],i=f.attr("data-full"),j=i?i.split("-"):[],g=O(j[0],j[1]-1,j[2]),l=O(g.getFullYear(),g.getMonth(),g.getDate(),k.getHours(),k.getMinutes(),k.getSeconds()),o=f.hasClass("mbsc-selected"),d=a(p.target),n=d[0],r,q;if(at||!f.hasClass("mbsc-cal-day-diff")){if(S&&h.contains(n))for(;n!=h;){if(d.hasClass("mbsc-cal-txt")||d.hasClass("mbsc-cal-txt-more")){if(r=d.attr("data-index"),q=aF[g],!1===m("onLabelTap",{date:l,domEvent:p,target:d[0],labels:q,label:q[r]}))return;break}n=(d=d.parent())[0]}!1===m("onDayChange",c(ai[i],{date:l,target:h,selected:o}))||b.readonly||f.hasClass("mbsc-disabled")||e._selectDay(f,g,l,o)}}function bg(a){F(Z),P(b.getDate(b.getYear(f.first),a.attr("data-val"),1),!0,!0)}function bl(a){F(_),P(b.getDate(a.attr("data-val"),b.getMonth(f.first),1),!0,!0)}function $(a,g,c){var d=b.getYear(a),e=b.getMonth(a),f=a.getDay(),h=0<s-f?7:0;return g?b.getDate(d,e,1):b.getDate(d,e,(void 0===c?s:c)-h-f+b.getDay(a))}function x(a,c){var d=b.getYear(a),e=b.getMonth(a),f=b.getDay(a);return p?b.getDate(d,e+c,1):b.getDate(d,e,f+c*h*7)}function aS(a,c){var d=12*Math.floor(b.getYear(a)/12);return b.getDate(d+12*c,0,1)}function aq(b,c,d,f){c&&e._isVisible&&(b.queue.push(arguments),1==b.queue.length&&function u(b,c,t,s){var d,h,o="",f=b.$scroller,i=b.buffer,j=b.offset,n=b.pages,r=b.total,l=b.first,q=b.genPage,k=b.getFirst,g=0<c?Math.min(c,i):Math.max(c,-i),p=b.pos*w+g-c+j,m=Math.abs(c)>i;b.callback&&(b.load(),b.callback(!0)),b.first=k(l,c),b.pos+=g*w,b.changing=!0,b.load=function(){if(m){for(d=0;d<n;d++)o+=q(k(l,h=c+d-j),p+h);0<c?(a(".mbsc-cal-slide",f).slice(-n).remove(),f.append(o)):c<0&&(a(".mbsc-cal-slide",f).slice(0,n).remove(),f.prepend(o))}},b.callback=function(t){var o=Math.abs(g),n="";if(e._isVisible){for(d=0;d<o;d++)n+=q(k(l,h=c+d-j-i+(0<c?r-o:0)),p+h);if(0<c?(f.append(n),a(".mbsc-cal-slide",f).slice(0,g).remove()):c<0&&(f.prepend(n),a(".mbsc-cal-slide",f).slice(g).remove()),m){for(n="",d=0;d<o;d++)n+=q(k(l,h=c+d-j-i+(0<c?0:r-o)),p+h);0<c?(a(".mbsc-cal-slide",f).slice(0,g).remove(),f.prepend(n)):c<0&&(a(".mbsc-cal-slide",f).slice(g).remove(),f.append(n))}ar(b),s&&!t&&s(),b.callback=null,b.load=null,b.queue.shift(),m=!1,b.queue.length?u.apply(this,b.queue[0]):(b.changing=!1,b.onAfterChange(b.first))}},b.onBeforeChange(b.first),b.load&&(b.load(),b.scroller.scroll(-b.pos*b.size,t?200:0,!1,b.callback))}(b,c,d,f))}function aH(h,r,p,g,k,i,j,s,l,m,n,f,q){var d=o?"Y":"X",c={$scroller:a(".mbsc-cal-scroll",h),queue:[],buffer:g,offset:k,pages:i,first:s,total:j,pos:0,min:r,max:p,genPage:f,getFirst:q,onBeforeChange:m,onAfterChange:n};return c.scroller=new X(h,{axis:d,easing:"",contSize:0,maxSnapScroll:g,mousewheel:void 0===b.mousewheel?o:b.mousewheel,time:200,lock:!0,rtl:T,stopProp:!1,minScroll:0,maxScroll:0,onBtnTap:function(b){"touchend"==b.domEvent.type&&Q(),l(a(b.target),b.domEvent)},onAnimationStart:function(){c.changing=!0},onAnimationEnd:function(a){f&&aq(c,Math.round((-c.pos*c.size-a["pos"+d])/c.size)*w)}}),e._scrollers.push(c.scroller),c}function ar(a,g){var b,d=0,e=0,f=a.first;if(!a.changing||!g){if(a.getFirst){for(d=a.buffer,e=a.buffer;e&&a.getFirst(f,e+a.pages-a.offset-1)>a.max;)e--;for(;d&&a.getFirst(f,1-d-a.offset)<=a.min;)d--}b=Math.round(an/a.pages),C&&b&&a.size!=b&&a.$scroller[o?"height":"width"](b),c(a.scroller.settings,{snap:b,minScroll:(-a.pos*w-e)*b,maxScroll:(-a.pos*w+d)*b}),a.size=b,a.scroller.refresh()}}function aN(a){e._onRefresh(a),e._isVisible&&n&&h&&(f&&f.changing?ak=a:(aJ(k,a,!0),aI(y)))}return l={},R=[],N={},m=e.trigger,aZ=c({},e.settings),ae=(b=c(e.settings,cL,aZ)).controls.join(","),s=b.firstDay,T=b.rtl,z=b.pageBuffer,W=b.weekCounter,h=b.weeks,p=6==h,o="vertical"==b.calendarScroll,bd=e._getRespCont(),aL="full"==b.weekDays?"":"min"==b.weekDays?"Min":"Short",aE=b.layout||("inline"==b.display||/top|bottom/.test(b.display)&&b.touchUi?"liquid":""),ad=(C="liquid"==aE)?null:b.calendarWidth,w=T&&!o?-1:1,aK="mbsc-disabled "+(b.disabledClass||""),aj="mbsc-selected "+(b.selectedTabClass||""),G="mbsc-selected "+(b.selectedClass||""),al=Math.max(1,Math.floor(((b.calendarHeight||0)/h-45)/18)),ae.match(/calendar/)&&(l.calendar=1,n=!0),ae.match(/date/)&&!n&&(l.date=1),ae.match(/time/)&&(l.time=1),b.controls.forEach(function(a){l[a]&&R.push(a)}),I=b.quickNav&&n&&p,D=b.yearChange&&p,C&&n&&"center"==b.display&&(e._isFullScreen=!0),b.layout=aE,b.preset=(l.date||n?"date":"")+(l.time?"time":""),aM=bc.call(this,e),ah=D?b.monthNamesShort:b.monthNames,ao=b.yearSuffix||"",ag=(b.dateWheels||b.dateFormat).search(/m/i),ay=(b.dateWheels||b.dateFormat).search(/y/i),E=e._format,b.min&&(r=H(g(b.min,E,b)),af=b.getYear(r),a$=b.getMonth(r),aY=b.getDate(12*Math.floor(af/12),0,1)),b.max&&(v=H(g(b.max,E,b)),ac=b.getYear(v),aU=b.getMonth(v),aQ=b.getDate(12*Math.floor(ac/12),0,1)),e.refresh=function(){aN(!1)},e.redraw=function(){aN(!0)},e.navigate=function(a,c){P(g(a,E,b),!0,c)},e.changeTab=function(b){e._isVisible&&l[b]&&u!=b&&(u=b,a(".mbsc-cal-tab",i).removeClass(aj).removeAttr("aria-selected"),a('.mbsc-cal-tab[data-control="'+b+'"]',i).addClass(aj).attr("aria-selected","true"),U&&(L.addClass("mbsc-cal-h"),l[u].removeClass("mbsc-cal-h")),"calendar"==u&&P(e.getDate(!0),!1,!0),e._showDayPicker(),e.trigger("onTabChange",{tab:u}))},e._checkSize=!0,e._onGenMonth=d,e._onSelectShow=d,e._onSetDate=d,e._onRefresh=d,e._getDayProps=d,e._prepareObj=V,e._showDayPicker=function(){I&&(F(_),F(Z))},e._selectDay=e.__selectDay=function(d,f,c){var a=e.live;az=b.outerMonthChange,ab=!0,e.setDate(c,a,1e3,!a,!0),a&&m("onSet",{valueText:e._value})},c(aM,{labels:null,compClass:"mbsc-calendar mbsc-dt mbsc-sc",onMarkupReady:function(g){var c=0;i=a(g.target),au=a(".mbsc-fr-c",i),y=e.getDate(!0),an=0,n&&(aB=!(!b.marked&&!b.data||b.labels||b.multiLabel||b.showEventCount),az=!0,u="calendar",j="auto"==b.months?Math.max(1,Math.min(3,Math.floor((ad||dp(bd))/280))):+b.months,aC=j+2*z,o=o&&j<2,at=void(q=0)===b.showOuterDays?j<2&&!o:b.showOuterDays,k=$(aP(y),p),au.append(function(){var c,a,d,f,e,i,g="",l=T?b.btnCalNextClass:b.btnCalPrevClass,m=T?b.btnCalPrevClass:b.btnCalNextClass;for(e='<div class="mbsc-cal-btn-w"><div data-step="-1" role="button" tabindex="0" aria-label="'+b.prevMonthText+'" class="'+l+' mbsc-cal-prev mbsc-cal-prev-m mbsc-cal-btn mbsc-fr-btn mbsc-fr-btn-e"></div>',a=0;a<(h?j:1);a++)e+='<div role="button" class="mbsc-cal-month"></div>';if(e+='<div data-step="1" role="button" tabindex="0" aria-label="'+b.nextMonthText+'" class="'+m+' mbsc-cal-next mbsc-cal-next-m mbsc-cal-btn mbsc-fr-btn mbsc-fr-btn-e"></div></div>',D&&(g='<div class="mbsc-cal-btn-w"><div data-step="-12" role="button" tabindex="0" aria-label="'+b.prevYearText+'" class="'+l+' mbsc-cal-prev mbsc-cal-prev-y mbsc-cal-btn mbsc-fr-btn mbsc-fr-btn-e"></div><div role="button" class="mbsc-cal-year"></div><div data-step="12" role="button" tabindex="0" aria-label="'+b.nextYearText+'" class="'+m+' mbsc-cal-next mbsc-cal-next-y mbsc-cal-btn mbsc-fr-btn mbsc-fr-btn-e"></div></div>'),h&&(i=aJ(k)),c='<div class="mbsc-w-p mbsc-cal-c"><div class="mbsc-cal '+(p?"":" mbsc-cal-week-view")+(1<j?" mbsc-cal-multi ":"")+(W?" mbsc-cal-weeks ":"")+(o?" mbsc-cal-vertical":"")+(aB?" mbsc-cal-has-marks":"")+(S?" mbsc-cal-has-labels":"")+(at?"":" mbsc-cal-hide-diff ")+(b.calendarClass||"")+'"'+(C?"":' style="width:'+(ad||280*j)+'px;"')+'><div class="mbsc-cal-hdr">'+(ay<ag||1<j?g+e:e+g)+"</div>",h){for(c+='<div class="mbsc-cal-body"><div class="mbsc-cal-day-picker"><div class="mbsc-cal-days-c">',d=0;d<j;d++){for(c+='<div class="mbsc-cal-days">',a=0;a<7;a++)c+='<div class="mbsc-cal-week-day'+(f=(a+s)%7)+'" aria-label="'+b.dayNames[f]+'">'+b["dayNames"+aL][f]+"</div>";c+="</div>"}c+='</div><div class="mbsc-cal-scroll-c mbsc-cal-day-scroll-c '+(b.calendarClass||"")+'"'+(b.calendarHeight?' style="height:'+b.calendarHeight+'px"':"")+'><div class="mbsc-cal-scroll" style="width:'+100/j+'%">'+i+"</div></div>"}if(c+="</div>",I){for(c+='<div class="mbsc-cal-month-picker mbsc-cal-picker mbsc-cal-h"><div class="mbsc-cal-scroll-c '+(b.calendarClass||"")+'"><div class="mbsc-cal-scroll">',a=0;a<3;a++){for(c+='<div class="mbsc-cal-slide"'+aA(a-1)+'><div role="grid" class="mbsc-cal-table"><div class="mbsc-cal-row">',d=0;d<12;d++)d&&d%3==0&&(c+='</div><div class="mbsc-cal-row">'),c+='<div role="gridcell"'+(1==a?' tabindex="-1" aria-label="'+b.monthNames[d]+'" data-val="'+d+'"':"")+' class="mbsc-cal-cell'+(1==a?" mbsc-btn-e":"")+'"><div class="mbsc-cal-cell-i mbsc-cal-cell-txt">'+(1==a?b.monthNamesShort[d]:"&nbsp;")+"</div></div>";c+="</div></div></div>"}for(c+="</div></div></div>",c+='<div class="mbsc-cal-year-picker mbsc-cal-picker mbsc-cal-h"><div class="mbsc-cal-scroll-c '+(b.calendarClass||"")+'"><div class="mbsc-cal-scroll">',a=-1;a<2;a++)c+=aV(aS(k,a),a);c+="</div></div></div>"}return c+="</div></div></div>"}()),Y=a(".mbsc-cal-month",i),aG=a(".mbsc-cal-year",i),B=a(".mbsc-cal-day-scroll-c",i)),I&&(_=a(".mbsc-cal-year-picker",i),Z=a(".mbsc-cal-month-picker",i)),L=a(".mbsc-w-p",i),1<R.length&&au.before(function(){var a,c;return a='<div class="mbsc-cal-tabs-c"><div class="mbsc-cal-tabs" role="tablist">',R.forEach(function(d,e){c=b[("calendar"==d?"date":d)+"Text"],a+='<div role="tab" aria-controls="'+bj.id+"-mbsc-pnl-"+e+'" class="mbsc-cal-tab mbsc-fr-btn-e '+(e?"":aj)+'" data-control="'+d+'"'+(b.tabLink?'><a href="#">'+c+"</a>":' tabindex="0">'+c)+"</div>"}),a+="</div></div>"}()),["date","time","calendar"].forEach(function(a){l[a]?(l[a]=L.eq(c),c++):"date"==a&&!l.date&&n&&(L.eq(c).remove(),c++)}),R.forEach(function(a){au.append(l[a])}),!n&&l.date&&l.date.css("position","relative"),e._scrollers=[],function(){if(n&&h){var c=a(".mbsc-cal-scroll-c",i);f=aH(c[0],r,v,z,q,j,aC,k,bb,bh,aT,aW,x),I&&(J=aH(c[1],null,null,1,0,1,3,k,bg),K=aH(c[2],aY,aQ,1,0,1,3,k,bl,d,d,aV,aS),e.tap(Y,function(){aX(Z),F(_)}),e.tap(aG,function(){aX(_),F(Z)})),aO(a(".mbsc-cal-btn",i),function(c,a,d,b){aq(f,a,!0,b)}),aT(k),null===b.defaultValue&&!e._hasValue||e._multiple||(e._activeElm=f.$active[0]),B.on("keydown",function(g){var d,a=b.getYear(y),c=b.getMonth(y),e=b.getDay(y);switch(g.keyCode){case 32:bb(f.$active,g);break;case 37:d=b.getDate(a,c,e-1*w);break;case 39:d=b.getDate(a,c,e+1*w);break;case 38:d=b.getDate(a,c,e-7);break;case 40:d=b.getDate(a,c,e+7);break;case 36:d=b.getDate(a,c,1);break;case 35:d=b.getDate(a,c+1,0);break;case 33:d=g.altKey?b.getDate(a-1,c,e):p?b.getDate(a,c-1,e):b.getDate(a,c,e-7*h);break;case 34:d=g.altKey?b.getDate(a+1,c,e):p?b.getDate(a,c+1,e):b.getDate(a,c,e+7*h)}d&&(g.preventDefault(),P(d,!0,!1,!0))})}e.tap(a(".mbsc-cal-tab",i),function(){e.changeTab(a(this).attr("data-control"))})}()},onShow:function(){n&&h&&aD(p?k:y)},onHide:function(){e._scrollers.forEach(function(a){a.destroy()}),u=K=J=f=ai=null},onValidated:function(f){var b,d,a=f.index,c=e._order;d=e.getDate(!0),ab?b="calendar":void 0!==a&&(b=c.dd==a||c.d==a||c.m==a||c.y==a?"date":"time"),m("onSetDate",{date:d,control:b}),"time"!==b&&P(d,!1,!!f.time,ab&&!e._multiple),ab=!1},onPosition:function(c){var d,w,t,g,r,s,p,E=c.oldHeight,m=c.windowHeight,y,x,z,A,v;if(U=(c.hasTabs||!0===b.tabs||!1!==b.tabs&&C)&&1<R.length,C&&(c.windowWidth>=b.breakPointMd?a(c.target).addClass("mbsc-fr-md"):a(c.target).removeClass("mbsc-fr-md")),U?(i.addClass("mbsc-cal-tabbed"),u=a(".mbsc-cal-tab.mbsc-selected",i).attr("data-control"),L.addClass("mbsc-cal-h"),l[u].removeClass("mbsc-cal-h")):(i.removeClass("mbsc-cal-tabbed"),L.removeClass("mbsc-cal-h")),e._isFullScreen&&(B.height(""),p=m-(r=c.popup.offsetHeight)+B[0].offsetHeight,r<=m&&B.height(p)),S&&h&&m!=E&&(y=p||B[0].offsetHeight,x=B.find(".mbsc-cal-txt-ph")[0],z=x.offsetTop,A=x.offsetHeight,v=Math.max(1,Math.floor((y/h-z)/(A+2))),al!=v&&(al=v,e.redraw())),n&&h){if(g=(s=C||o||U?B[0][o?"offsetHeight":"offsetWidth"]:ad||280*j)!=an,an=s,C&&g&&D)for(ah=b.maxMonthWidth>Y[0].offsetWidth?b.monthNamesShort:b.monthNames,w=b.getYear(k),t=b.getMonth(k),d=0;d<j;d++)Y.eq(d).text(ah[b.getMonth(b.getDate(w,t-q+d,1))]);g&&ar(f,!0)}I&&g&&(ar(J,!0),ar(K,!0))}})}cN={separator:" ",dateFormat:"mm/dd/yy",dateDisplay:"MMddyy",timeFormat:"h:ii A",dayText:"Day",monthText:"Month",yearText:"Year",hourText:"Hours",minuteText:"Minutes",ampmText:"&nbsp;",secText:"Seconds",nowText:"Now",todayText:"Today"},cL={controls:["calendar"],firstDay:0,weekDays:"short",maxMonthWidth:170,breakPointMd:768,months:1,pageBuffer:1,weeks:6,highlight:!0,outerMonthChange:!0,quickNav:!0,yearChange:!0,tabs:"auto",todayClass:"mbsc-cal-today",btnCalPrevClass:"mbsc-ic mbsc-ic-arrow-left6",btnCalNextClass:"mbsc-ic mbsc-ic-arrow-right6",dateText:"Date",timeText:"Time",todayText:"Today",fromText:"Start",toText:"End",moreEventsText:"{count} more",prevMonthText:"Previous Month",nextMonthText:"Next Month",prevYearText:"Previous Year",nextYearText:"Next Year"},cI={},i.calendar=function(e){function n(a){var c,e,f,h=null;if(d={},a&&a.length)for(e=0;e<a.length;e++)c=g(a[e],i,b,b.isoParts),h=h||c,d[O((f=c).getFullYear(),f.getMonth(),f.getDate())]=c;return h}function m(){e.redraw()}var h,l,i,q,o,t=c({},e.settings),b=c(e.settings,cI,t),p="mbsc-selected "+(b.selectedClass||""),k=b.defaultValue,f="multiple"==b.select||1<b.select||"week"==b.selectType,r=s(b.select)?b.select:1/0,d={};return h=bj.call(this,e),q=void 0===b.firstSelectDay?b.firstDay:b.firstSelectDay,i=e._format,f&&n(k),e._multiple=f,e._getDayProps=function(a){return{selected:f?void 0!==d[a]:void 0}},e._selectDay=function(o,c,k,n){var h,i,g,j;if(b.setOnDayTap&&"multiple"!=b.select&&"inline"!=b.display)return e.setDate(k),void e.select();if(f)if("week"==b.selectType){g=c.getDay()-q;for(g=g<0?7+g:g,"multiple"!=b.select&&(d={}),h=0;h<7;h++)i=O(c.getFullYear(),c.getMonth(),c.getDate()-g+h),n?delete d[i]:bb(d).length/7<r&&(d[i]=i);m()}else j=a('.mbsc-cal-day[data-full="'+o.attr("data-full")+'"]',l),n?(j.removeClass(p).removeAttr("aria-selected"),delete d[c]):bb(d).length<r&&(j.addClass(p).attr("aria-selected","true"),d[c]=c);e.__selectDay(o,c,k)},e.setVal=function(a,b,c,d,g){f&&(a=n(a)),e._setVal(a,b,c,d,g),f&&m()},e.getVal=function(g){var a,c=[];if(f){for(a in d)c.push(ad(d[a],b,i));return c}return ad(e.getDate(g),b,i)},c({},h,{highlight:!f,outerMonthChange:!f,parseValue:function(a){return f&&a&&"string"==typeof a&&(a=n(a.split(","))),f&&k&&k.length&&(b.defaultValue=k[0]),h.parseValue.call(this,a)},formatValue:function(g){var a,c=[];if(f){for(a in d)c.push(j(i,d[a],b));return c.join(", ")}return h.formatValue.call(this,g,e)},onClear:function(){f&&(d={},m())},onBeforeShow:function(){void 0!==b.setOnDayTap||b.buttons&&b.buttons.length||1!=b.controls.length||(b.setOnDayTap=!0),b.setOnDayTap&&"inline"!=b.display&&(b.outerMonthChange=!1),b.counter&&f&&(b.headerText=function(){var c=0,e="week"==b.selectType?7:1;return a.each(d,function(){c++}),(1<(c=Math.round(c/e))&&b.selectedPluralText||b.selectedText).replace(/{count}/,c)})},onMarkupReady:function(b){h.onMarkupReady.call(this,b),l=a(b.target),f&&(a(".mbsc-fr-hdr",l).attr("aria-live","off"),o=c({},d))},onCancel:function(){!e.live&&f&&(d=c({},o))}})},h("calendar",k),aq="mbsc-input-wrap",bp=["touchstart","touchmove","touchend","touchcancel","mousedown","mousemove","mouseup","mouseleave"],cE={tap:Y};function cD(d,h){var b={},i=d[0],e=d.parent(),g=e.find(".mbsc-err-msg"),j=d.attr("data-icon-align")||"left",f=d.attr("data-icon");e.hasClass(aq)?e=e.parent():a('<span class="'+aq+'"></span>').insertAfter(d).append(d),g&&e.find("."+aq).append(g),f&&(-1!==f.indexOf("{")?b=JSON.parse(f):b[j]=f),"file"==i.type&&(b.right=d.attr("data-icon-upload")||"upload"),(f||h)&&(c(b,h),e.addClass((b.right?"mbsc-ic-right ":"")+(b.left?" mbsc-ic-left":"")).find("."+aq).append('<span class="mbsc-input-fill"></span>').append(b.left?'<span class="mbsc-input-ic mbsc-left-ic mbsc-ic mbsc-ic-'+b.left+'"></span>':"").append(b.right?'<span class="mbsc-input-ic mbsc-right-ic mbsc-ic mbsc-ic-'+b.right+'"></span>':""))}function cz(d,e,b,c,f){"segmented"==e?d.closest(".mbsc-segmented").addClass("box"==b?"mbsc-input-box":"").addClass("outline"==b?"mbsc-input-outline":""):"button"!=e&&"submit"!=e&&(d.addClass("mbsc-control-w").addClass("box"==b?"mbsc-input-box":"").addClass("outline"==b?"mbsc-input-outline":"").addClass("inline"==c?"mbsc-label-inline":"").addClass("stacked"==c?"mbsc-label-stacked":"").addClass("floating"==c?"mbsc-label-floating":"").addClass("floating"==c&&f.value?"mbsc-label-floating-active":"").find("label").addClass("mbsc-label").each(function(c,b){a(b).attr("title",a(b).text())}),d.contents().filter(function(){return 3==this.nodeType&&this.nodeValue&&/\S/.test(this.nodeValue)}).each(function(){a('<span class="mbsc-label" title="'+this.textContent.trim()+'"></span>').insertAfter(this).append(this)}))}function cx(c){var a=b.themes.form[c];return a&&a.addRipple?a:null}function cw(b,c,d){var a=b.attr(c);return void 0===a||""===a?d:a}function bv(a){var c=b.themes.form[a.theme].baseTheme;return"mbsc-"+a.theme+(c?" mbsc-"+c:"")+(a.rtl?" mbsc-rtl":" mbsc-ltr")}ab=function(){function d(g,m){var n=this,e,f,j,h,i,k,o,l;F(this,d),e=c({},cE,b.settings,m),f=a(g),j=f.parent(),h=j.hasClass("mbsc-input-wrap")?j.parent():j,i=f.next().hasClass("mbsc-fr")?f.next():null,k=aY(f),o=cw(f,"data-input-style",e.inputStyle),l=cw(f,"data-label-style",e.labelStyle),g.mbscInst&&g.mbscInst.destroy(),i&&i.insertAfter(h),e.theme=cp(e),void 0===e.rtl&&e.lang&&b.i18n[e.lang]&&(e.rtl=b.i18n[e.lang].rtl),cz(h,k,o,l,g),f.addClass("mbsc-control"),this._handle=this._handle.bind(this),bp.forEach(function(a){f.on(a,n._handle)}),this.settings=e,this._type=k,this._elm=g,this._$elm=f,this._$parent=h,this._$frame=i,this._ripple=cx(e.theme),this._isFloating="floating"==l||h.hasClass("mbsc-label-floating"),this.cssClass=bv(e),this.getClassElm().addClass(this.cssClass),g.mbscInst=this}return _(d,[{key:"getClassElm",value:function(){return this._$parent}},{key:"destroy",value:function(){var a=this;this._$elm.removeClass("mbsc-control"),this.getClassElm().removeClass(this.cssClass),bp.forEach(function(b){a._$elm.off(b,a._handle)}),delete this._elm.mbscInst}},{key:"option",value:function(b){c(this.settings,b);var a=this.getClassElm();this.cssClass&&a.removeClass(this.cssClass),this.cssClass=bv(this.settings),a.addClass(this.cssClass),this._ripple=cx(this.settings.theme)}},{key:"_handle",value:function(a){switch(a.type){case"touchstart":case"mousedown":this._onStart(a);break;case"touchmove":case"mousemove":this._onMove(a);break;case"touchend":case"touchcancel":case"mouseup":case"mouseleave":this._onEnd(a)}}},{key:"_addRipple",value:function(a){this._ripple&&this._$rippleElm&&this._ripple.addRipple(this._$rippleElm,a)}},{key:"_removeRipple",value:function(){this._ripple&&this._$rippleElm&&this._ripple.removeRipple()}},{key:"_onStart",value:function(a){var b=this._elm;N(a,b)&&(this._startX=e(a,"X"),this._startY=e(a,"Y"),aI&&aI.removeClass("mbsc-active"),b.disabled||(this._isActive=!0,(aI=this._$elm).addClass("mbsc-active"),this._addRipple(a))),"touchstart"==a.type&&this._$elm.closest(".mbsc-no-touch").removeClass("mbsc-no-touch")}},{key:"_onMove",value:function(a){(this._isActive&&9<Math.abs(e(a,"X")-this._startX)||9<Math.abs(e(a,"Y")-this._startY))&&(this._$elm.removeClass("mbsc-active"),this._removeRipple(),this._isActive=!1)}},{key:"_onEnd",value:function(a){var b=this,c=this._elm,d=this._type;this._isActive&&this.settings.tap&&"touchend"==a.type&&!c.readOnly&&bQ(c,d,a),this._isActive&&setTimeout(function(){b._$elm.removeClass("mbsc-active"),b._removeRipple()},100),this._isActive=!1,aI=null}}]),d}(),b.themes.form.mobiscroll={},bx=["focus","change","blur","animationstart"],ar=function(){function b(g,i){var c,d,e,f,h;return F(this,b),d=(c=T(this,r(b).call(this,g,i)))._$elm,e=c._$parent,f=e.find(".mbsc-select-input, .mbsc-color-input"),(!function(h,i,b){var f={},c=b[0],g=b.attr("data-password-toggle"),d=b.attr("data-icon-show")||"eye",e=b.attr("data-icon-hide")||"eye-blocked";g&&(f.right="password"==c.type?d:e),cD(b,f),g&&bV(h,i.find(".mbsc-right-ic").addClass("mbsc-input-toggle"),function(){"text"==c.type?(c.type="password",a(this).addClass("mbsc-ic-"+d).removeClass("mbsc-ic-"+e)):(c.type="text",a(this).removeClass("mbsc-ic-"+d).addClass("mbsc-ic-"+e))})}(an(c),e,d),c._checkLabel=c._checkLabel.bind(an(c)),c._mouseDown=c._mouseDown.bind(an(c)),c._setText=c._setText.bind(an(c)),"file"==g.type)&&(h=e.find(".mbsc-file-input"),c._$input=h.length?h:a('<input type="text" class="'+(d.attr("class")||"")+' mbsc-file-input" placeholder="'+(d.attr("placeholder")||"")+'"/>').insertAfter(d),d.on("change",c._setText)),e.addClass("mbsc-input").on("mousedown",c._mouseDown),bx.forEach(function(a){d.on(a,c._checkLabel)}),f.length&&(d.after(f),f.hasClass("mbsc-select-input")&&(c._delm=f[0],c.refresh())),c}return $(b,ab),_(b,[{key:"_setText",value:function(d){for(var b=d.target.files,c=[],a=0;a<b.length;++a)c.push(b[a].name);this._$input.val(c)}},{key:"_checkLabel",value:function(a){if(this._isFloating){var b=this._delm||this._elm;b.value||document.activeElement===b||a&&("focus"==a.type||"animationstart"==a.type&&this._$elm.is("*:-webkit-autofill"))?this._$parent.addClass("mbsc-label-floating-active"):this._$parent.removeClass("mbsc-label-floating-active")}}},{key:"_mouseDown",value:function(a){document.activeElement===this._elm&&a.target!==this._elm&&a.preventDefault()}},{key:"refresh",value:function(){this._checkLabel()}},{key:"destroy",value:function(){var a=this;af(r(b.prototype),"destroy",this).call(this),this._$parent.off("mousedown",this._mouseDown).removeClass("mbsc-ic-left mbsc-ic-right").find(".mbsc-input-ic").remove(),this._$parent.find(".mbsc-input-fill").remove(),bx.forEach(function(b){a._$elm.off(b,a._checkLabel)}),this._$elm.off("change",this._setText)}}]),b}(),o("[mbsc-input]",ar),bz=function(){function a(e,f){var c,b,d;return F(this,a),b=(c=T(this,r(a).call(this,e,f)))._$elm,d=b.attr("data-icon"),b.addClass("mbsc-btn mbsc-no-touch").find(".mbsc-btn-ic").remove(),d&&(b.prepend('<span class="mbsc-btn-ic mbsc-ic mbsc-ic-'+d+'"></span>'),""===b.text()&&b.addClass("mbsc-btn-icon-only")),c._$rippleElm=b,c}return $(a,ab),_(a,[{key:"getClassElm",value:function(){return this._$elm}}]),a}(),o("[mbsc-button]",bz),bA=function(){function a(c,d){var b;return F(this,a),(b=T(this,r(a).call(this,c,d)))._$parent.prepend(b._$elm).addClass("mbsc-checkbox mbsc-control-w").find(".mbsc-checkbox-box").remove(),b._$elm.after('<span class="mbsc-checkbox-box"></span>'),b}return $(a,ab),a}(),o("[mbsc-checkbox]",bA),bB=function(){function a(c,d){var b;return F(this,a),(b=T(this,r(a).call(this,c,d)))._$parent.addClass("mbsc-radio mbsc-control-w").find(".mbsc-radio-box").remove(),b._$elm.after('<span class="mbsc-radio-box"><span></span></span>'),b}return $(a,ab),a}(),o("[mbsc-radio]",bB),bC=function(){function b(i,h){var c,e,f,g,d;return F(this,b),e=(c=T(this,r(b).call(this,i,h)))._$elm,f=c._$parent,g=f.find(".mbsc-select-input"),d=g.length?g:a('<input tabindex="-1" class="mbsc-select-input mbsc-control" readonly>'),c._$input=d,c._delm=d[0],c._setText=c._setText.bind(an(c)),f.addClass("mbsc-select"+(c._$frame?" mbsc-select-inline":"")),e.after(d),d.after('<span class="mbsc-select-ic mbsc-ic mbsc-ic-arrow-down5"></span>'),e.on("change",c._setText),c._setText(),c}return $(b,ar),_(b,[{key:"destroy",value:function(){af(r(b.prototype),"destroy",this).call(this),this._$parent.find(".mbsc-select-ic").remove(),this._$elm.off("change",this._setText)}},{key:"_setText",value:function(){var b=this._elm,c=a(b);c.is("select")&&!c.hasClass("mbsc-comp")&&this._$input.val(-1!=b.selectedIndex?b.options[b.selectedIndex].text:""),this.refresh()}}]),b}(),o("[mbsc-dropdown]",bC),bE=["change","keydown","input","scroll"];function cq(){clearTimeout(bD),bD=setTimeout(function(){a("textarea.mbsc-control").each(function(){au(this)})},100)}function au(b){var c,e,d,f=a(b).attr("rows")||6;b.offsetHeight&&(b.style.height="",d=b.scrollHeight-b.offsetHeight,c=b.offsetHeight+(0<d?d:0),f<(e=Math.round(c/24))?(c=24*f+(c-24*e),a(b).addClass("mbsc-textarea-scroll")):a(b).removeClass("mbsc-textarea-scroll"),c&&(b.style.height=c+"px"))}q&&a(window).on("resize orientationchange",cq),bH=function(){function b(c,d){var a;return F(this,b),(a=T(this,r(b).call(this,c,d)))._$parent.addClass("mbsc-textarea"),bE.forEach(function(b){a._$elm.on(b,a._handle)}),au(c),a}return $(b,ar),_(b,[{key:"destroy",value:function(){var a=this;af(r(b.prototype),"destroy",this).call(this),bE.forEach(function(b){a._$elm.off(b,a._handle)})}},{key:"refresh",value:function(){af(r(b.prototype),"refresh",this).call(this),clearTimeout(this._debounce),au(this._elm)}},{key:"_handle",value:function(c){switch(af(r(b.prototype),"_handle",this).call(this,c),c.type){case"change":au(this._elm);break;case"keydown":case"input":this._onInput(c);break;case"scroll":!function(b){var c=a(b),e,d;c.hasClass("mbsc-textarea-scroll")||(e=b.scrollHeight-b.offsetHeight,d=b.offsetHeight+e,Math.round(d/24)<=(c.attr("rows")||6)&&(b.scrollTop=0,b.style.height=d+"px"))}(this._elm)}}},{key:"_onInput",value:function(){var a=this;clearTimeout(this._debounce),this._debounce=setTimeout(function(){au(a._elm)},100)}}]),b}(),o("[mbsc-textarea]",bH),bI=function(){function b(i,h){var d,f,c,g,e;return F(this,b),g=(d=T(this,r(b).call(this,i,h)))._$elm,e=d._$parent,e.hasClass("mbsc-segmented-item-ready")||(f=a('<div class="mbsc-segmented mbsc-no-touch"></div>'),e.after(f),e.parent().find('input[name="'+g.attr("name")+'"]').each(function(){var b=a(this);c=b.parent().addClass("mbsc-segmented-item mbsc-segmented-item-ready"),a('<span class="mbsc-segmented-content">'+(b.attr("data-icon")?'<span class="mbsc-ic mbsc-ic-'+b.attr("data-icon")+'"></span>':"")+"</span>").append(c.contents()).appendTo(c),c.prepend(b),f.append(c)})),d._$rippleElm=g.next(),d}return $(b,ab),_(b,[{key:"getClassElm",value:function(){return this._$elm.closest(".mbsc-segmented")}}]),b}(),o("[mbsc-segmented]",bI);function aZ(j,f){var w,v,u,t,n,m,r,A,B,i,q,g,l,c,o,e,k="",h=this,d=a(j),z=c;function y(){var b;j.disabled||(b=parseFloat(a(this).val()),s(isNaN(b)?c:b))}function D(){return j.disabled}function E(b,a){s(c+a*i)}function s(e,a,b){z=c,void 0===a&&(a=!0),void 0===b&&(b=a),c=x(e),u.removeClass("mbsc-disabled"),a&&d.val(c),c==m?v.addClass("mbsc-disabled"):c==n&&w.addClass("mbsc-disabled"),c!==z&&b&&d.trigger("change")}function p(b,c,e){var a=d.attr(b);return void 0===a||""===a?c:e?a:+a}function x(a){return+Math.min(n,Math.max(Math.round(a/i)*i,m)).toFixed(B)}C.call(this,j,f,!0),h.getVal=function(){var a=parseFloat(d.val());return x(a=isNaN(a)?c:a)},h.setVal=function(a,b,d){a=parseFloat(a),s(isNaN(a)?c:a,b,d)},h._init=function(){o=d.parent().hasClass("mbsc-stepper"),e=o?d.closest(".mbsc-stepper-cont"):d.parent(),g=h.settings,m=void 0===f.min?p("min",g.min):f.min,n=void 0===f.max?p("max",g.max):f.max,i=void 0===f.step?p("step",g.step):f.step,B=Math.abs(i)<1?(i+"").split(".")[1].length:0,r=void 0===f.inputStyle?p("data-input-style",g.inputStyle,!0):f.inputStyle,t=d.attr("data-val")||g.val,c=x(+j.value||0),l=b.themes.form[g.theme],A=l&&l.addRipple?l:null,o||e.addClass("mbsc-stepper-cont mbsc-no-touch mbsc-control-w").addClass("box"==r?"mbsc-input-box":"").addClass("outline"==r?"mbsc-input-outline":"").append('<span class="mbsc-segmented mbsc-stepper"></span>').find(".mbsc-stepper").append('<span class="mbsc-segmented-item mbsc-stepper-control mbsc-stepper-minus '+(c==m?"mbsc-disabled":"")+'" data-step="-1" tabindex="0"><span class="mbsc-segmented-content"><span class="mbsc-ic mbsc-ic-minus"></span></span></span>').append('<span class="mbsc-segmented-item mbsc-stepper-control mbsc-stepper-plus '+(c==n?"mbsc-disabled":"")+'"  data-step="1" tabindex="0"><span class="mbsc-segmented-content"> <span class="mbsc-ic mbsc-ic-plus"></span></span></span>').prepend(d),k&&(e.removeClass(k),e.find(".mbsc-segmented").removeClass(k)),k="mbsc-"+g.theme+(l.baseTheme?" mbsc-"+l.baseTheme:"")+(g.rtl?" mbsc-rtl":" mbsc-ltr"),e.addClass(k),e.find(".mbsc-segmented").addClass(k),v=a(".mbsc-stepper-minus",e),w=a(".mbsc-stepper-plus",e),u=a(".mbsc-stepper-control",e),o||("left"==t?(e.addClass("mbsc-stepper-val-left"),d.after('<span class="mbsc-segmented-item"><span class="mbsc-segmented-content"></span></span>')):"right"==t?(e.addClass("mbsc-stepper-val-right"),w.after('<span class="mbsc-segmented-item"><span class="mbsc-segmented-content"></span></span>')):v.after('<span class="mbsc-segmented-item"><span class="mbsc-segmented-content mbsc-stepper-val"></span></span>')),q||(d.on("change",y),q=aO(u,E,150,D,!1,A)),d.val(c).attr("data-role","stepper").attr("min",m).attr("max",n).attr("step",i).addClass("mbsc-control"),j.mbscInst=h},h._destroy=function(){d.removeClass("mbsc-control").off("change",y),q.destroy(),delete j.mbscInst},h.init()}aZ.prototype={_class:"stepper",_hasDef:!0,_hasTheme:!0,_hasLang:!0,_defaults:{min:0,max:100,step:1}},o("[mbsc-stepper]",l.Stepper=aZ);function co(f,j,i){var g,c,e,h,b=this;C.call(this,f,j,!0),b.__init=d,b.__destroy=d,b._init=function(){var d;h=b.settings,g=a(f),d=!!c,c=(c=g.parent()).hasClass("mbsc-input-wrap")?c.parent():c,b._$parent=c,e&&c.removeClass(e),e=b._css+" mbsc-progress-w mbsc-control-w "+bv(h),c.addClass(e),g.addClass("mbsc-control"),b.__init(),d||b._attachChange(),b.refresh(),f.mbscInst=b},b._destroy=function(){b.__destroy(),c.removeClass(e),g.removeClass("mbsc-control"),delete f.mbscInst},i||b.init()}function cm(E,ab,ac){var g,K,D,m,M,o,t,p,x,F,R,U,H,s,$,f,l,r,z,P,n,h,w,q,A,k,i,I,J,V,L,y,O,c,b=this,S=new Date;function T(b){"mousedown"===b.type&&b.preventDefault(),!N(b,this)||p&&!l||E.disabled||E.readOnly||(i.stopProp&&b.stopPropagation(),F=w=!(p=!0),J=e(b,"X"),V=e(b,"Y"),s=J,t.removeClass("mbsc-progress-anim"),K=q?a(".mbsc-slider-handle",this):m,D&&D.removeClass("mbsc-handle-curr"),D=K.parent().addClass("mbsc-active mbsc-handle-curr"),g.addClass("mbsc-active"),f=+K.attr("data-index"),O=t[0].offsetWidth,H=t[0].getBoundingClientRect().left,"mousedown"===b.type&&(r=!0,a(document).on("mousemove",u).on("mouseup",B)),"mouseenter"===b.type&&(l=!0,a(document).on("mousemove",u)))}function u(a){p&&(s=e(a,"X"),$=e(a,"Y"),R=s-J,U=$-V,5<Math.abs(R)&&(w=!0),(w||r||l)&&50<Math.abs(S-new Date)&&(S=new Date,Y(s,i.round,P&&(!l||r))),w?a.preventDefault():7<Math.abs(U)&&"touchmove"==a.type&&G())}function B(a){p&&(a.preventDefault(),q||t.addClass("mbsc-progress-anim"),l&&!r?j(c[f],f,!1,!1,!0):Y(s,!0,!0),w||F||("touchend"==a.type&&Q(),b._onTap(c[f])),"mouseup"==a.type&&(r=!1),"mouseleave"==a.type&&(l=!1),l||G())}function W(){p&&G()}function X(){var e=b._readValue(a(this)),d=+a(this).attr("data-index");e!==c[d]&&(c[d]=e,j(A[d]=e,d))}function aa(a){a.stopPropagation()}function Z(a){a.preventDefault()}function _(d){var b;if(!E.disabled){switch(d.keyCode){case 38:case 39:b=1;break;case 40:case 37:b=-1}b&&(d.preventDefault(),y||(f=+a(this).attr("data-index"),j(c[f]+k*b,f,!0),y=setInterval(function(){j(c[f]+k*b,f,!0)},200)))}}function C(a){a.preventDefault(),clearInterval(y),y=null}function G(){p=!1,D.removeClass("mbsc-active"),g.removeClass("mbsc-active"),a(document).off("mousemove",u).off("mouseup",B)}function Y(c,d,e){var a=d?Math.min(Math[b._rounding||"round"](Math.max(100*(c-H)/O,0)/I/k)*k*100/(n-h+x),100):Math.max(0,Math.min(100*(c-H)/O,100));z&&(a=100-a),j(Math.round((h-x+a/I)*L)/L,f,e,a)}function j(a,d,f,g,j,i){var k=m.eq(d),e=k.parent();a=Math.min(n,Math.max(a,h)),void 0===i&&(i=f),b._update?a=b._update(a,c,d,g,q,j,e):e.css({left:z?"auto":(g||v(a,h,n))+"%",right:z?(g||v(a,h,n))+"%":"auto"}),h<a?e.removeClass("mbsc-slider-start"):(c[d]>h||j)&&e.addClass("mbsc-slider-start"),f&&(c[d]=a),f&&A[d]!=a&&(F=!0,A[d]=a,b._fillValue(a,d,i)),k.attr("aria-valuenow",a)}co.call(this,E,ab,!0),b._onTap=d,b.___init=d,b.___destroy=d,b._attachChange=function(){g.on(i.changeEvent,X)},b.__init=function(){var d;m&&(d=!0,m.parent().remove()),b.___init(),o=b._$parent,t=b._$track,g=o.find("input"),i=b.settings,h=b._min,n=b._max,x=b._base||0,k=b._step,P=b._live,L=k%1!=0?100/(100*(k%1).toFixed(2)):1,I=100/(n-h+x)||100,q=1<g.length,z=i.rtl,c=[],A=[],g.each(function(d){c[d]=b._readValue(a(this)),a(this).attr("data-index",d)}),m=o.find(".mbsc-slider-handle"),M=o.find(q?".mbsc-slider-handle-cont":".mbsc-progress-cont"),m.on("keydown",_).on("keyup",C).on("blur",C),M.on("touchstart mousedown"+(i.hover?" mouseenter":""),T).on("touchmove",u).on("touchend touchcancel"+(i.hover?" mouseleave":""),B).on("pointercancel",W),d||(g.on("click",aa),o.on("click",Z))},b.__destroy=function(){o.off("click",Z),g.off(i.changeEvent,X).off("click",aa),m.off("keydown",_).off("keyup",C).off("blur",C),M.off("touchstart mousedown mouseenter",T).off("touchmove",u).off("touchend touchcancel mouseleave",B).off("pointercancel",W),b.___destroy()},b.refresh=function(){g.each(function(c){j(b._readValue(a(this)),c,!0,!1,!0,!1)})},b.getVal=function(){return q?c.slice(0):c[0]},b.setVal=b._setVal=function(b,e,d){a.isArray(b)||(b=[b]),a.each(b,function(a,b){c[a]=b}),a.each(b,function(a,b){j(b,a,!0,!1,!0,d)})},ac||b.init()}function aW(e,h){var d,i,f,g,b=this;c(h=h||{},{changeEvent:"click",round:!1}),cm.call(this,e,h,!0),b._readValue=function(){return e.checked?1:0},b._fillValue=function(a,c,b){d.prop("checked",!!a),b&&d.trigger("change")},b._onTap=function(a){b._setVal(a?0:1)},b.___init=function(){f=b.settings,d=a(e),(i=d.parent()).find(".mbsc-switch-track").remove(),i.prepend(d),d.attr("data-role","switch").after('<span class="mbsc-progress-cont mbsc-switch-track"><span class="mbsc-progress-track mbsc-progress-anim"><span class="mbsc-slider-handle-cont"><span class="mbsc-slider-handle mbsc-switch-handle" data-index="0"><span class="mbsc-switch-txt-off">'+f.offText+'</span><span class="mbsc-switch-txt-on">'+f.onText+"</span></span></span></span></span>"),g&&g.destroy(),g=new ab(e,f),b._$track=i.find(".mbsc-progress-track"),b._min=0,b._max=1,b._step=1},b.___destroy=function(){g.destroy()},b.getVal=function(){return e.checked},b.setVal=function(a,c,d){b._setVal(a?1:0,c,d)},b.init()}aW.prototype={_class:"switch",_css:"mbsc-switch",_hasTheme:!0,_hasLang:!0,_hasDef:!0,_defaults:{stopProp:!0,offText:"Off",onText:"On"}},o("[mbsc-switch]",l.Switch=aW);function av(p,g,v){var k,d,f,t,l,n,e,i,s,r,c,o,j,m,b=this;function u(){var a=h("value",e);a!==j&&q(a)}function h(b,c,e){var a=d.attr(b);return void 0===a||""===a?c:e?a:+a}function q(a,g,c,f){a=Math.min(i,Math.max(a,e)),t.css("width",100*(a-e)/(i-e)+"%"),void 0===c&&(c=!0),void 0===f&&(f=c),a===j&&!g||b._display(a),a!==j&&(j=a,c&&d.attr("value",j),f&&d.trigger("change"))}co.call(this,p,g,!0),b._display=function(a){m=o&&c.returnAffix?o.replace(/\{value\}/,a).replace(/\{max\}/,i):a,l&&l.html(m),k&&k.html(m)},b._attachChange=function(){d.on("change",u)},b.__init=function(){var q,u,m,v;if(c=b.settings,d=a(p),v=!!f,f=b._$parent,e=b._min=void 0===g.min?h("min",c.min):g.min,i=b._max=void 0===g.max?h("max",c.max):g.max,s=void 0===g.inputStyle?h("data-input-style",c.inputStyle,!0):g.inputStyle,r=void 0===g.labelStyle?h("data-label-style",c.labelStyle,!0):g.labelStyle,j=h("value",e),q=d.attr("data-val")||c.val,m=(m=d.attr("data-step-labels"))?JSON.parse(m):c.stepLabels,o=d.attr("data-template")||(100!=i||c.template?c.template:"{value}%"),v?(q&&(k.remove(),f.removeClass("mbsc-progress-value-"+("right"==q?"right":"left"))),m&&a(".mbsc-progress-step-label",n).remove()):(cz(f,null,s,r,p),cD(d),f.find(".mbsc-input-wrap").append('<span class="mbsc-progress-cont"><span class="mbsc-progress-track mbsc-progress-anim"><span class="mbsc-progress-bar"></span></span></span>'),t=b._$progress=f.find(".mbsc-progress-bar"),n=b._$track=f.find(".mbsc-progress-track")),d.attr("min",e).attr("max",i),q&&(k=a('<span class="mbsc-progress-value"></span>'),f.addClass("mbsc-progress-value-"+("right"==q?"right":"left")).find(".mbsc-input-wrap").append(k)),m)for(u=0;u<m.length;++u)n.append('<span class="mbsc-progress-step-label" style="'+(c.rtl?"right":"left")+": "+100*(m[u]-e)/(i-e)+'%" >'+m[u]+"</span>");l=a(d.attr("data-target")||c.target)},b.__destroy=function(){f.removeClass("mbsc-ic-left mbsc-ic-right").find(".mbsc-progress-cont").remove(),f.find(".mbsc-input-ic").remove(),d.off("change",u)},b.refresh=function(){q(h("value",e),!0,!1)},b.getVal=function(){return j},b.setVal=function(a,b,c){q(a,!0,b,c)},v||b.init()}av.prototype={_class:"progress",_css:"mbsc-progress",_hasTheme:!0,_hasLang:!0,_hasDef:!0,_defaults:{min:0,max:100,returnAffix:!0}},o("[mbsc-progress]",l.Progress=av);function ak(q,m,r){var e,f,i,p,n,l,k,o,g,c,d,j,h,b=this,s,t,u,w;av.call(this,q,m,!0),s=b.__init,t=b.__destroy,cm.call(this,q,m,!0),u=b.__init,w=b.__destroy,b.__init=function(){s(),u()},b.__destroy=function(){t(),w()},b._update=function(a,e,f,h,j,m,n){return o?0===f?(a=Math.min(a,e[1]),i.css({width:v(e[1],d,c)-v(a,d,c)+"%",left:g?"auto":v(a,d,c)+"%",right:g?v(a,d,c)+"%":"auto"})):(a=Math.max(a,e[0]),i.css({width:v(a,d,c)-v(e[0],d,c)+"%"})):j||!l?n.css({left:g?"auto":(h||v(a,d,c))+"%",right:g?(h||v(a,d,c))+"%":"auto"}):i.css("width",(h||v(a,d,c))+"%"),k&&p.eq(f).html(a),j||e[f]==a&&!m||b._display(a),a},b._readValue=function(a){return+a.val()},b._fillValue=function(b,a,c){e.eq(a).val(b),c&&e.eq(a).trigger("change")},b._markupReady=function(){var b,h;if(k&&f.addClass("mbsc-slider-has-tooltip"),1!=j)for(h=(c-d)/j,b=0;b<=h;++b)n.append('<span class="mbsc-slider-step" style="'+(g?"right":"left")+":"+100/h*b+'%"></span>');e.each(function(b){"range"==this.type&&a(this).attr("min",d).attr("max",c).attr("step",j),(l?i:n).append('<span class="mbsc-slider-handle-cont'+(o&&!b?" mbsc-slider-handle-left":"")+'"><span tabindex="0" class="mbsc-slider-handle" aria-valuemin="'+d+'" aria-valuemax="'+c+'" data-index="'+b+'"></span>'+(k?'<span class="mbsc-slider-tooltip"></span>':"")+"</span>")}),p=f.find(".mbsc-slider-tooltip")},b.___init=function(){f&&(f.removeClass("mbsc-slider-has-tooltip"),1!=j&&a(".mbsc-slider-step",n).remove()),f=b._$parent,n=b._$track,i=b._$progress,e=f.find("input"),h=b.settings,d=b._min,c=b._max,b._step=j=void 0===m.step?+e.attr("step")||h.step:m.step,b._live=br("data-live",h.live,e),k=br("data-tooltip",h.tooltip,e),l=br("data-highlight",h.highlight,e)&&e.length<3,o=l&&2==e.length,g=h.rtl,b._markupReady()},r||b.init()}ak.prototype={_class:"progress",_css:"mbsc-progress mbsc-slider",_hasTheme:!0,_hasLang:!0,_hasDef:!0,_defaults:{changeEvent:"change",stopProp:!0,min:0,max:100,step:1,live:!0,highlight:!0,round:!0,returnAffix:!0}},o("[mbsc-slider]",l.Slider=ak);function aT(j,l,m){var c,f,d,g,h,i,e,b=this,k=a(j);ak.call(this,j,l,!0),b._update=function(a,e,f,i,g,h){return c.css("width",v(a,0,d)+"%"),g||e[f]==a&&!h||b._display(a),a},b._markupReady=function(){var a,j="",l="";for(f=b._$track,c=b._$progress,e=b.settings,g=b._min,d=b._max,b._base=g,b._rounding=e.rtl?"floor":"ceil",h=k.attr("data-empty")||e.empty,i=k.attr("data-filled")||e.filled,a=0;a<d;++a)j+='<span class="mbsc-ic mbsc-ic-'+h+'"></span>',l+='<span class="mbsc-ic mbsc-ic-'+i+'"></span>';f.html(j),f.append(c),c.html(l),f.append('<span class="mbsc-rating-handle-cont"><span tabindex="0" class="mbsc-slider-handle" aria-valuemin="'+g+'" aria-valuemax="'+d+'" data-index="0"></span></span>')},m||b.init()}aT.prototype={_class:"progress",_css:"mbsc-progress mbsc-rating",_hasTheme:!0,_hasLang:!0,_hasDef:!0,_defaults:{changeEvent:"change",stopProp:!0,min:1,max:5,step:1,live:!0,round:!0,hover:!0,highlight:!0,returnAffix:!0,empty:"star",filled:"star3"}},o("[mbsc-rating]",l.Rating=aT),ck=1,aU=function(){function b(i,f){var e,d,g,h=this,c,j;F(this,b),c=a(i),(this.settings=f,this._isOpen=f.isOpen||!1,c.addClass("mbsc-collapsible "+(this._isOpen?"mbsc-collapsible-open":"")),(e=(g=c.hasClass("mbsc-card")?(d=c.find(".mbsc-card-header").eq(0).addClass("mbsc-collapsible-header"),c.find(".mbsc-card-content").eq(0).addClass("mbsc-collapsible-content")):c.hasClass("mbsc-form-group")||c.hasClass("mbsc-form-group-inset")?(d=c.find(".mbsc-form-group-title").eq(0).addClass("mbsc-collapsible-header"),c.find(".mbsc-form-group-content").eq(0).addClass("mbsc-collapsible-content")):(d=c.find(".mbsc-collapsible-header").eq(0),c.find(".mbsc-collapsible-content").eq(0)))[0])&&!e.id&&(e.id="mbsc-collapsible-"+ck++),d.length&&e)&&(j=a('<span class="mbsc-collapsible-icon mbsc-ic mbsc-ic-arrow-down5"></span>'),bV(this,d,function(){h.collapse()}),d.attr("role","button").attr("aria-expanded",this._isOpen).attr("aria-controls",e.id).attr("tabindex","0").on("mousedown",function(a){a.preventDefault()}).on("keydown",function(a){32!==a.which&&13!=a.keyCode||(a.preventDefault(),h.collapse())}).append(j)),(i.mbscInst=this)._$header=d,this._$content=g,this._$elm=c,this._$accordionParent=c.parent("[mbsc-accordion], mbsc-accordion, .mbsc-accordion"),this.show=this.show.bind(this),this.hide=this.hide.bind(this),this.toggle=this.toggle.bind(this)}return _(b,[{key:"collapse",value:function(a){var c=this._$elm,b=this._$content;void 0===a&&(a=!this._isOpen),a&&this._isOpen||!a&&!this._isOpen||!b.length||(a?(aL&&b.on("transitionend",function a(){b.off("transitionend",a).css("height","")}).css("height",b[0].scrollHeight),c.addClass("mbsc-collapsible-open")):(aL&&b.css("height",getComputedStyle(b[0]).height),setTimeout(function(){b.css("height",0),c.removeClass("mbsc-collapsible-open")},50)),a&&this._$accordionParent&&this._$accordionParent.find(".mbsc-collapsible-open").each(function(){this!==c[0]&&this.mbscInst.hide()}),this._isOpen=a,this._$header.attr("aria-expanded",this._isOpen))}},{key:"show",value:function(){this.collapse(!0)}},{key:"hide",value:function(){this.collapse(!1)}},{key:"toggle",value:function(){this.collapse()}},{key:"destroy",value:function(){this._$elm.removeClass("mbsc-collapsible mbsc-collapsible-open"),this._$content.removeClass("mbsc-collapsible-content"),this._$header.removeClass("mbsc-collapsible-header").find(".mbsc-collapsible-icon").remove()}}]),b}(),l.CollapsibleBase=aU,bS=0;function ch(d,c,b,e){a("input,select,textarea,progress,button",d).each(function(){var d=this,e=a(d),f=aY(e);if("false"!=e.attr("data-enhance"))if(e.hasClass("mbsc-control"))d.mbscInst&&d.mbscInst.option({theme:b.theme,lang:b.lang,rtl:b.rtl,onText:b.onText,offText:b.offText,stopProp:b.stopProp});else switch(d.id||(d.id="mbsc-form-control-"+ ++bS),f){case"button":case"submit":c[d.id]=new bz(d,{theme:b.theme,rtl:b.rtl,tap:b.tap});break;case"switch":c[d.id]=new aW(d,{theme:b.theme,lang:b.lang,rtl:b.rtl,tap:b.tap,onText:b.onText,offText:b.offText,stopProp:b.stopProp});break;case"checkbox":c[d.id]=new bA(d,{tap:b.tap,theme:b.theme,rtl:b.rtl});break;case"range":a(d).parent().hasClass("mbsc-slider")||(c[d.id]=new ak(d,{theme:b.theme,lang:b.lang,rtl:b.rtl,stopProp:b.stopProp,labelStyle:b.labelStyle}));break;case"rating":c[d.id]=new aT(d,{theme:b.theme,lang:b.lang,rtl:b.rtl,stopProp:b.stopProp});break;case"progress":c[d.id]=new av(d,{theme:b.theme,lang:b.lang,rtl:b.rtl,labelStyle:b.labelStyle});break;case"radio":c[d.id]=new bB(d,{tap:b.tap,theme:b.theme,rtl:b.rtl});break;case"select":case"select-one":case"select-multiple":c[d.id]=new bC(d,{tap:b.tap,inputStyle:b.inputStyle,labelStyle:b.labelStyle,theme:b.theme,rtl:b.rtl});break;case"textarea":c[d.id]=new bH(d,{tap:b.tap,inputStyle:b.inputStyle,labelStyle:b.labelStyle,theme:b.theme,rtl:b.rtl});break;case"segmented":c[d.id]=new bI(d,{theme:b.theme,rtl:b.rtl,tap:b.tap,inputStyle:b.inputStyle});break;case"stepper":c[d.id]=new aZ(d,{theme:b.theme,rtl:b.rtl});break;case"hidden":return;default:c[d.id]=new ar(d,{tap:b.tap,inputStyle:b.inputStyle,labelStyle:b.labelStyle,theme:b.theme,rtl:b.rtl})}}),a("[data-collapsible]:not(.mbsc-collapsible)",d).each(function(){var b=this,d=a(b).attr("data-open");b.id||(b.id="mbsc-form-control-"+ ++bS),c[b.id]=new aU(b,{isOpen:void 0!==d&&"false"!=d}),B[b.id]=c[b.id]}),e||cq()}function bU(g,j){var e,b,f="",d=a(g),h={},c=this;function i(){d.removeClass("mbsc-no-touch")}C.call(this,g,j,!0),c.refresh=function(a){ch(d,h,e,a)},c._init=function(){var h=void 0!==e.collapsible||void 0!==d.attr("data-collapsible"),a;d.hasClass("mbsc-card")||d.on("touchstart",i).show(),f&&d.removeClass(f),f="mbsc-card mbsc-form mbsc-no-touch mbsc-"+e.theme+(ce?" mbsc-form-hb":"")+(e.baseTheme?" mbsc-"+e.baseTheme:"")+(e.rtl?" mbsc-rtl":" mbsc-ltr"),d.addClass(f).removeClass("mbsc-cloak"),h&&!b&&(a=d.attr("data-open"),b=new aU(g,{isOpen:void 0!==a&&"false"!=a||!0===e.collapsible})),c.refresh()},c._destroy=function(){for(var a in d.removeClass(f).off("touchstart",i),h)h[a].destroy();b&&b.destroy()},c.toggle=function(){b&&b.toggle()},c.hide=function(){b&&b.hide()},c.show=function(){b&&b.show()},e=c.settings,c.init()}ce="ios"==m&&7<x;function aQ(b){var c=[Math.round(b.r).toString(16),Math.round(b.g).toString(16),Math.round(b.b).toString(16)];return a.each(c,function(b,a){1==a.length&&(c[b]="0"+a)}),"#"+c.join("")}function aM(a){return{r:(a=parseInt(-1<a.indexOf("#")?a.substring(1):a,16))>>16,g:(65280&a)>>8,b:255&a,toString:function(){return"rgb("+this.r+","+this.g+","+this.b+")"}}}function cd(h){var b,c,d,e=h.h,i=255*h.s/100,a=255*h.v/100,f,g;return 0==i?b=c=d=a:(f=(255-i)*a/255,g=e%60*(a-f)/60,360==e&&(e=0),e<60?(b=a,c=(d=f)+g):e<120?(d=f,b=(c=a)-g):e<180?(c=a,d=(b=f)+g):e<240?(b=f,c=(d=a)-g):e<300?(d=a,b=(c=f)+g):e<360?(c=f,d=(b=a)-g):b=c=d=0),{r:b,g:c,b:d,toString:function(){return"rgb("+this.r+","+this.g+","+this.b+")"}}}function ca(a){var e,f,c=0,g=Math.min(a.r,a.g,a.b),b=Math.max(a.r,a.g,a.b),d=b-g;return c=(e=(f=b)?255*d/b:0)?a.r==b?(a.g-a.b)/d:a.g==b?2+(a.b-a.r)/d:4+(a.r-a.g)/d:-1,(c*=60)<0&&(c+=360),{h:c,s:e*=100/255,v:f*=100/255,toString:function(){return"hsv("+Math.round(this.h)+","+Math.round(this.s)+"%,"+Math.round(this.v)+"%)"}}}function b_(i){var b,h,e=i.r/255,c=i.g/255,d=i.b/255,a=Math.max(e,c,d),f=Math.min(e,c,d),j=(a+f)/2,g;if(a==f)b=h=0;else{switch(g=a-f,h=.5<j?g/(2-a-f):g/(a+f),a){case e:b=(c-d)/g+(c<d?6:0);break;case c:b=(d-e)/g+2;break;case d:b=(e-c)/g+4}b/=6}return{h:Math.round(360*b),s:Math.round(100*h),l:Math.round(100*j),toString:function(){return"hsl("+this.h+","+this.s+"%,"+this.l+"%)"}}}function b$(a){return b_(aM(a))}function du(a){return aQ(function(i){var e,d,j,h,b,c,a=i.h,g=i.s,f=i.l;return isFinite(a)||(a=0),isFinite(g)||(g=0),isFinite(f)||(f=0),(a/=60)<0&&(a=6- -a%6),a%=6,g=Math.max(0,Math.min(1,g/100)),f=Math.max(0,Math.min(1,f/100)),c=(b=(1-Math.abs(2*f-1))*g)*(1-Math.abs(a%2-1)),j=a<1?(e=b,d=c,0):a<2?(e=c,d=b,0):a<3?(e=0,d=b,c):a<4?(e=0,d=c,b):a<5?(e=c,d=0,b):(e=b,d=0,c),h=f-b/2,{r:Math.round(255*(e+h)),g:Math.round(255*(d+h)),b:Math.round(255*(j+h)),toString:function(){return"rgb("+this.r+","+this.g+","+this.b+")"}}}(a))}function cb(a){return aQ(cd(a))}function cc(a){return ca(aM(a))}bU.prototype={_hasDef:!0,_hasTheme:!0,_hasLang:!0,_class:"card",_defaults:{tap:Y,stopProp:!0,rtl:!1}},o("[mbsc-card]",l.Card=bU,!0),h("card",bU,!1);function bN(A,U,V){var f,Q,d,w,t,u,v,s,x,o,i,K,n,O,B,e,D,j,P,T,q,p,k,m,I,b=this,r=a(A),h=0,l={},g={};function J(f,e,h){if(!h){b._value=b._hasValue?b._tempValue.slice(0):null;for(var a=0;a<d.length;++a)d[a].tempChangedColor&&b._value&&-1!=b._value.indexOf(d[a].tempChangedColor)&&(d[a].changedColor=d[a].tempChangedColor),delete d[a].tempChangedColor}f&&(b._isInput&&r.val(b._hasValue?b._tempValue:""),w("onFill",{valueText:b._hasValue?b._tempValue:"",change:e}),e&&(l=c(!0,{},g),b._preventChange=!0,r.trigger("change")),R(b._value,!0))}function M(b,a){return'<div class="mbsc-color-input-item" data-color="'+(void 0!==(a=void 0!==a?a:H(b))?a:b)+'" style="background: '+b+';">'+(j?"":'<div class="mbsc-color-input-item-close mbsc-ic mbsc-ic-material-close"></div>')+"</div>"}function N(a){K[0].style.background=a?y+"linear-gradient(left, "+(f.rtl?"#000000":"#FFFFFF")+" 0%, "+a+" 50%, "+(f.rtl?"#FFFFFF":"#000000")+" 100%)":""}function H(a){if(Object.keys(g).length&&!isNaN(a))return a;for(var b in d)if(a==d[b].color||a==d[b].changedColor)return b}function C(b,d){var c,a=b.match(/\d+/gim);switch(!0){case-1<b.indexOf("rgb"):c=aQ({r:a[0],g:a[1],b:a[2]});break;case-1<b.indexOf("hsl"):c=du({h:a[0],s:a[1],l:a[2]});break;case-1<b.indexOf("hsv"):c=cb({h:a[0],s:a[1],v:a[2]});break;case-1<b.indexOf("#"):c=b}return function(a,b){switch(b){case"rgb":return aM(a);case"hsl":return b$(a);case"hsv":return cc(a);default:return a}}(c,d||f.format)}function E(b,c){a(".mbsc-color-active",c).removeClass("mbsc-color-active"),n&&(b.parent().addClass("mbsc-color-active"),i&&b&&void 0!==h&&k.eq(h).parent().addClass("mbsc-color-active"))}function R(r,z){var m,s,o=[],x=0,w=a.map(d,function(a){return a.changedColor||a.color}),v,y;if(j)r=a.isArray(r)?r[0]:r,-1<(s=w.indexOf(r))&&o.push(s),r&&!o.length&&n&&(v=+a(".mbsc-color-input-item",q).attr("data-color"),isNaN(v)?v=void 0:o.push(v),e=v);else if(r)if(i&&n)for(y in l)void 0!==l[y].colorIndex&&o.push(+l[y].colorIndex);else for(m=0;m<r.length;++m)-1<(s=w.indexOf(r[m]))&&(o.push(s),w[s]="temp"+m);for(m=0;m<o.length;++m)d[o[m]]&&F(!0,o[m],x++,d[o[m]].changedColor||d[o[m]].color,!0);for(m=0;m<d.length;++m)-1==o.indexOf(m)&&F(!1,m,void 0,d[m].changedColor||d[m].color,!1);if(i)for(m=x;m<f.select;++m)g[m]={},k&&k.eq(m).addClass("mbsc-color-preview-item-empty").css({background:"transparent"});l=c(!0,{},g),!1!==z&&function(){if(D){var c,m="";if(q.empty(),b._hasValue){if(j)m+=M(b._value,e);else for(c=0;c<b._value.length;++c)m+=M(b._value[c],Object.keys(g).length&&g[c].colorIndex?g[c].colorIndex:H(b._value[c]));q.append(m),b.tap(a(".mbsc-color-input-item",q),function(c){if(a(c.target).hasClass("mbsc-color-input-item-close")){var j=a(this).index();c.stopPropagation(),c.preventDefault(),void 0===e&&(e=a(c.target).parent().attr("data-color")),i&&d[e]&&(h=d[e].previewInd,k.eq(h).parent().removeClass("mbsc-color-active"),l[j]={},g[j]={}),b._value.splice(j,1),b.setVal(b._value,!0,!0)}else n&&"inline"!==f.display&&(e=a(c.target).attr("data-color"),isNaN(e)&&(e=H(e)),e&&d[e]&&(d[e].selected=!0,h=d[e].previewInd,setTimeout(function(){t.scroll(p.eq(e),400),i&&u.scroll(k.eq(h),400)},200)))})}}}()}function F(a,c,e,f,j,l){if(i&&j&&(g[e].colorIndex=a?c:void 0,g[e].color=a?f:void 0,k)){var h=k.eq(e);h.removeClass("mbsc-color-preview-item-empty").css({background:a?f:"transparent"}),a||h.addClass("mbsc-color-preview-item-empty").parent().removeClass("mbsc-color-active")}l&&(a?b._tempValue.splice(e,0,f):b._tempValue.splice(b._tempValue.indexOf(f),1)),p&&(a?p.eq(c).addClass("mbsc-color-selected"):p.eq(c).removeClass("mbsc-color-selected").parent().removeClass("mbsc-color-active")),d[c].previewInd=a?e:void 0,d[c].selected=a}function G(a,b){void 0!==a&&(j||d[a]&&d[a].selected)?d[e=a]&&(s=d[a].changedColor||d[a].color,m=p.eq(a),n&&(E(p.eq(a),b||""),(x=C(d[a].color,"hsl")).l=C(s,"hsl").l,N(d[a].color),B.setVal(100-x.l,!1,!1))):n&&N()}function S(c,d){var b=a(c.target).index();e=g[b].colorIndex,m=p.eq(e),h=b,G(e,d),t.scroll(m,250),w("onPreviewItemTap",{target:c.target,value:g[b].color,index:b})}function L(l,c){var o=!1,p=a(".mbsc-color-selected",c);if((m=a(l.target)).hasClass("mbsc-color-clear-item"))return s="",void b.clear();(j||P>+p.length||m.hasClass("mbsc-color-selected"))&&(e=m.attr("data-index"),i&&(h=void 0!==d[e].previewInd?d[e].previewInd:function(){var a;for(a=0;a<f.select;++a)if(void 0===g[a].colorIndex)return a}(),o=n&&m.hasClass("mbsc-color-selected")&&!m.parent().hasClass("mbsc-color-active"),6<k.length&&u.scroll(k.eq(h))),s=d[e].changedColor||d[e].color,j?(p.removeClass("mbsc-color-selected"),(b._tempValue=s)&&m.toggleClass("mbsc-color-selected"),E(m,c)):(E(m,c),o||F(!d[e].selected,e,h,s,!0,!0)),G(e,c),b.live&&(b._fillValue(),w("onSet",{value:b._value})),w("onItemTap",{target:l.target,value:s,selected:d[e].selected,index:e}),b._updateHeader())}z.call(this,A,U,!0),b.setVal=b._setVal=function(c,d,e,f){b._hasValue=null!=c,b._tempValue=j?a.isArray(c)?c[0]:c:a.isArray(c)?c:c?[c]:[],J(d,void 0===e?d:e,f)},b.getVal=b._getVal=function(a){return b._hasValue||a?T?function(){var a,b=[];for(a=0;a<d.length;++a)d[a].selected&&b.push(d[a]);return b}():b[a?"_tempValue":"_value"]:null},b._readValue=function(){var a=r.val()||"";b._hasValue=!1,0!==a.length&&""!==a&&(b._hasValue=!0),b._hasValue?(b._tempValue=j?a:"hex"==f.format?a.split(","):a.match(/[a-z]{3}\((\d+\.?\d{0,}?),\s*([\d.]+)%{0,},\s*([\d.]+)%{0,}\)/gim),J(!0)):b._tempValue=[],R(b._tempValue,b._hasValue)},b._fillValue=function(){J(b._hasValue=!0,!0)},b._generateContent=function(){var a,b,c,e=v?1:0,g;for(O=o?Math.ceil((d.length+e)/f.rows):f.rows,b='<div class="mbsc-color-scroll-cont mbsc-w-p '+(o?"":"mbsc-color-vertical")+'"><div class="mbsc-color-cont">'+(o?'<div class="mbsc-color-row">':""),a=0;a<d.length;++a)c=d[a].changedColor||d[a].color,v&&0===a&&(b+='<div class="mbsc-color-item-c"><div tabindex="0" class="mbsc-color-clear-item mbsc-btn-e mbsc-color-selected"><div class="mbsc-color-clear-cross"></div></div></div>'),0!==a&&(a+e)%O==0&&(b+=o?'</div><div class="mbsc-color-row">':""),b+='<div class="mbsc-color-item-c"><div tabindex="0" data-index="'+a+'" class="mbsc-color-item mbsc-btn-e mbsc-ic mbsc-ic-material-check mbsc-color-btn-e '+(d[a].selected?"mbsc-color-selected":"")+'"  style="background:'+c+'"></div></div>';if(b+="</div></div>"+(o?"</div>":""),n&&(b+='<div class="mbsc-color-slider-cont"><input class="mbsc-color-slider" type="range" data-highlight="false" value="50" min="0" max="100"/></div>'),i){for(g in b+='<div class="mbsc-color-preview-cont"><div class="mbsc-color-refine-preview">',l)b+='<div class="mbsc-color-preview-item-c mbsc-btn-e mbsc-color-btn-e" tabindex="0"><div class="mbsc-color-preview-item '+(l[g].color?"":"mbsc-color-preview-item-empty")+'" style="background: '+(l[g].color||"initial")+';"></div></div>';b+="</div></div>"}return b},b._position=function(c){var a,b;o||(a=c.find(".mbsc-color-cont"),b=Math.ceil(a.find(".mbsc-color-item-c")[0].offsetWidth),a.width(Math.min(Math.floor(c.find(".mbsc-fr-c").width()/b),Math.round(d.length/f.rows))*b+1)),t&&t.refresh(),u&&u.refresh()},b._markupInserted=function(a){o||a.find(".mbsc-color-scroll-cont").css("max-height",a.find(".mbsc-color-item-c")[0].offsetHeight*f.rows),t=new X(a.find(".mbsc-color-scroll-cont")[0],{axis:o?"X":"Y",rtl:f.rtl,elastic:60,stopProp:!1,mousewheel:f.mousewheel,onBtnTap:function(b){L(b,a)}})},b._attachEvents=function(c){var l;p=a(".mbsc-color-item",c),c.on("keydown",".mbsc-color-btn-e",function(a){a.stopPropagation(),32==a.keyCode&&(a.target.classList.contains("mbsc-color-item")?L(a,c):S(a,c))}),i&&(k=a(".mbsc-color-preview-item",c)),n&&(c.addClass("mbsc-color-refine"),I=a(".mbsc-color-slider",c),B=new ak(I[0],{theme:f.theme,rtl:f.rtl}),K=c.find(".mbsc-progress-track"),e&&b._value&&G(e,c),I.on("change",function(){void 0!==e&&(j||d[e]&&d[e].selected)&&(x.l=100-this.value,l=C(x.toString()).toString(),j?b._tempValue=l:b._tempValue[void 0!==h?h:b._tempValue.length]=l,d[e].tempChangedColor=l,p.eq(e).css("background",l),i&&(g[h].color=l,k.eq(h).removeClass("mbsc-color-preview-item-empty").css({background:l})),b.live&&bo(b._fillValue()))})),i&&(u=new X(c.find(".mbsc-color-preview-cont")[0],{axis:"X",rtl:f.rtl,stopProp:!1,mousewheel:f.mousewheel,onBtnTap:function(a){S(a,c)}})),b._updateHeader()},b._markupRemove=function(){t&&t.destroy(),B&&B.destroy(),u&&u.destroy()},b.__processSettings=function(){var c,e;if(f=b.settings,w=b.trigger,o="horizontal"==f.navigation,b._value=[],b._tempValue=[],j="single"==f.select,v=void 0!==f.clear?f.clear:j,!(e=f.data||[]).length)switch(f.format){case"rgb":e=["rgb(255,235,60)","rgb(255,153,0)","rgb(244,68,55)","rgb(234,30,99)","rgb(156,38,176)","rgb(104,58,183)","rgb(63,81,181)","rgb(33,150,243)","rgb(0,151,136)","rgb(75,175,79)","rgb(126,93,78)","rgb(158,158,158)"],v&&e.splice(10,0,"rgb(83, 71, 65)");break;case"hsl":e=["hsl(54,100%,62%)","hsl(36,100%,50%)","hsl(4,90%,59%)","hsl(340,83%,52%)","hsl(291,64%,42%)","hsl(262,52%,47%)","hsl(231,48%,48%)","hsl(207,90%,54%)","hsl(174,100%,30%)","hsl(122,40%,49%)","hsl(19,24%,40%)","hsl(0,0%,62%)"],v&&e.splice(10,0,"hsl(20, 12%, 29%)");break;default:e=["#ffeb3c","#ff9900","#f44437","#ea1e63","#9c26b0","#683ab7","#3f51b5","#2196f3","#009788","#4baf4f","#7e5d4e","#9e9e9e"],v&&e.splice(10,0,"#534741")}if(n="refine"==f.mode,i=!isNaN(f.select),P=isNaN(f.select)?j?2:e.length:f.select,T=a.isPlainObject(e[0]),i&&!Object.keys(l).length)for(c=0;c<f.select;++c)l[c]={},g[c]={};for(d=e.slice(0),c=0;c<d.length;++c)a.isPlainObject(e[c])?d[c].color=e[c].color:(e[c]=e[c].toLowerCase(),d[c]={key:c,name:e[c],color:e[c]});Q=f.defaultValue||d[0].color,x=C(s=Q,"hsl"),(D=f.enhance&&r.is("input"))&&(r.hasClass("mbsc-color-input-hdn")?q=r.prev():((q=a("<div "+(A.placeholder?'data-placeholder="'+A.placeholder+'"':"")+' class="mbsc-control mbsc-color-input '+(f.inputClass||"")+'" readonly ></div>')).insertBefore(r),r.addClass("mbsc-color-input-hdn").attr("tabindex",-1)),f.anchor=q,b.attachShow(q))},b.__destroy=function(){D&&(r.removeClass("mbsc-color-input-hdn"),q.remove())},b._checkSize=!0,V||b.init()}bN.prototype={_hasDef:!0,_hasTheme:!0,_hasLang:!0,_class:"color",_defaults:c({},z.prototype._defaults,{headerText:!1,validate:d,parseValue:d,enhance:!0,rows:2,select:"single",format:"hex",navigation:"horizontal",compClass:"mbsc-color"})},l.Color=bN,b.themes.color=b.themes.frame,h("color",bN,!(ae.color={hsv2hex:cb,hsv2rgb:cd,rgb2hsv:ca,rgb2hex:aQ,rgb2hsl:b_,hex2rgb:aM,hex2hsv:cc,hex2hsl:b$})),i.date=bc,i.time=bc,i.datetime=bc,h("date",k),h("time",k),h("datetime",k),w=function(g,i,h){function f(b){a(".mbsc-fr-c",b).hasClass("mbsc-wdg-c")||(a(".mbsc-fr-c",b).addClass("mbsc-wdg-c").append(d.show()),a(".mbsc-w-p",b).length||a(".mbsc-fr-c",b).addClass("mbsc-w-p"))}var b,e,d=a(g),c=this;z.call(this,g,i,!0),c._generateContent=function(){return""},c._markupReady=function(a){"inline"!=b.display&&f(a)},c._markupInserted=function(a){"inline"==b.display&&f(a),a.trigger("mbsc-enhance",[{theme:b.theme,lang:b.lang}])},c._markupRemove=function(){d.hide(),e&&e.parent().length&&e.after(d)},c.__processSettings=function(){b=c.settings,c.buttons.ok={text:b.okText,icon:b.okIcon,handler:"set"},b.buttons=b.buttons||("inline"==b.display?[]:["ok"]),!e&&d.parent().length&&(e=a(document.createComment("popup")),d.before(e)),d.hide()},h||c.init()},w.prototype={_hasDef:!0,_hasTheme:!0,_hasContent:!0,_hasLang:!0,_responsive:!0,_class:"popup",_defaults:c({},z.prototype._defaults,{compClass:"mbsc-wdg",okText:"OK",headerText:!1})},l.Popup=w,cf=l.Widget=w,b.themes.popup=b.themes.frame,cg=0;function bT(a,b,c){"jsonp"==c?function(c,d){var a=document.createElement("script"),b="mbscjsonp"+ ++cg;window[b]=function(c){a.parentNode.removeChild(a),delete window[b],c&&d(c)},a.src=c+(0<=c.indexOf("?")?"&":"?")+"callback="+b,document.body.appendChild(a)}(a,b):function(b,c){var a=new XMLHttpRequest;a.open("GET",b,!0),a.onload=function(){200<=this.status&&this.status<400&&c(JSON.parse(this.response))},a.onerror=function(){},a.send()}(a,b)}ae.getJson=bT,ci={view:{calendar:{type:"month",popover:!0}},allDayText:"All-day",labelsShort:["Yrs","Mths","Days","Hrs","Mins","Secs"],eventText:"event",eventsText:"events",noEventsText:"No events"},cj={yearChange:!1,weekDays:"short"},i.eventcalendar=function(d,ah){function Z(w,E,v){var f,l,m,e,D=0,B=[],k="",r=[],c,o,h,x,z,p,C,s,u,F;for(v=v||d._prepareObj(i,w,E),f=H(w);f<=E;f.setDate(f.getDate()+1))(e=v[H(f)])&&e.length&&r.push({d:new Date(f),list:_(e)});if(0<r.length)for(l=0;l<r.length;l++){for(k+='<div><div class="mbsc-lv-gr-title mbsc-event-day" data-full="'+ap((e=r[l]).d)+'">'+j(b.dateFormat,e.d,b)+"</div>",m=0;m<e.list.length;m++)c=e.list[m],o=c.start?g(c.start):null,h=c.end?g(c.end):null,x=c.color,z=A.test(c.d)||M.test(c.d),p=o&&h&&!t(o,h),C=!p||t(o,e.d),s=!p||t(h,e.d),u=c.d?z?c.d:g(c.d):o,F=c.allDay||z||p&&!C&&!s,B.push({d:e.d,e:c}),k+='<div class="mbsc-lv-item mbsc-lv-item-actionable" data-index="'+D+'"><div class="mbsc-event-time">'+(F?b.allDayText:C&&u&&u.getTime?j(b.timeFormat,u):p&&s?b.toText:"")+(!F&&s&&h&&h.getTime?"<br/>"+j(b.timeFormat,h):"")+'</div><div class="mbsc-event-color"'+(x?' style="background:'+x+';"':"")+'></div><div class="mbsc-event-txt">'+c.text+"</div></div>",D++;k+="</div>"}else k+='<div class="mbsc-lv-gr-title mbsc-event-empty"><div class="mbsc-empty"><h3>'+b.noEventsText+"</h3></div></div>";y++,q.html('<div class="mbsc-lv mbsc-lv-v">'+k+"</div>").scrollTop(0),setTimeout(function(){y--},150),d.tap(a(".mbsc-lv-item",q),function(c){var b=B[a(this).attr("data-index")];n("onEventSelect",{domEvent:c,event:b.e,date:b.d})})}function ac(){if(o){var c=H(o.d);!function(e,h,o){if(e){var c,i,k,f,l='<div class="mbsc-cal-event-list">';c=a('<div class="mbsc-cal-events '+(b.eventBubbleClass||"")+'"><div class="mbsc-cal-events-i"><div class="mbsc-cal-events-sc"></div><div class="mbsc-sc-bar-c"><div class="mbsc-sc-bar"></div></div></div></div>'),i=a(".mbsc-cal-events-i",c),k=a(".mbsc-cal-events-sc",c),d.tap(i,function(){f.scrolled||u()}),m=new w(c[0],{display:"bubble",theme:b.theme,lang:b.lang,context:b.context,buttons:[],anchor:o,showOverlay:!1,cssClass:"mbsc-no-padding mbsc-cal-events-popup",onShow:function(){f=new X(i[0],{scrollbar:a(".mbsc-sc-bar-c",c),stopProp:!1})},onClose:function(b,a){a.destroy(),f.destroy()}}),C=o,e=_(e),a.each(e,function(u,c){var e=c.start?g(c.start):null,d=c.end?g(c.end):null,p=A.test(c.d)||M.test(c.d),i=c.d?p?c.d:g(c.d):e,f=e&&d&&!t(e,d),o=!f||t(e,h),s=!f||t(d,h),r=c.allDay||p||f&&!o&&!s,q=c.color,k="",m="",n=a("<div>"+c.text+"</div>").text();i.getTime&&(k=j((f?"MM d yy ":"")+b.timeFormat,i)),d&&(m=j((f?"MM d yy ":"")+b.timeFormat,d)),l+='<div role="button" title="'+n+'" aria-label="'+n+(k?", "+b.fromText+": "+k:"")+(m?", "+b.toText+": "+m:"")+'" class="mbsc-cal-event mbsc-lv-item mbsc-lv-item-actionable"><div class="mbsc-cal-event-color" style="'+(q?"background:"+q+";":"")+'"></div><div class="mbsc-cal-event-text"><div class="mbsc-cal-event-time">'+(r?b.allDayText:o&&i.getTime?j(b.timeFormat,i):"")+"</div>"+c.text+"</div>"+(e&&d&&!c.allDay?'<div class="mbsc-cal-event-dur">'+b.formatDuration(e,d,c)+"</div>":"")+"</div>"}),l+="</div>",k.html(l),m.show(),n("onEventBubbleShow",{target:C,eventList:c[0]}),d.tap(a(".mbsc-cal-event",k),function(b){f.scrolled||n("onEventSelect",{domEvent:b,event:e[a(this).index()],date:h})}),D=!0}}(o.events||v[c],c,o.cell||a('.mbsc-cal-slide-a .mbsc-cal-day[data-full="'+ap(c)+'"]',d._markup)[0]),o=null}}function _(a){return a.slice(0).sort(function(a,b){var c=a.start?g(a.start):null,d=b.start?g(b.start):null,e=a.end?g(a.end):null,f=b.end?g(b.end):null,l=A.test(a.d)||M.test(a.d),m=A.test(b.d)||M.test(b.d),h=a.d?l?a.d:g(a.d):c,i=b.d?m?b.d:g(b.d):d,j=h.getTime?c&&e&&c.toDateString()!==e.toDateString()?1:a.allDay?2:h.getTime():0,k=i.getTime?d&&f&&d.toDateString()!==f.toDateString()?1:b.allDay?2:i.getTime():0;return j==k?a.text>b.text?1:-1:j-k})}function af(){var c,e,b;y||a(".mbsc-event-day",this).each(function(){if(0<=(e=this.offsetTop-P.scrollTop)&&e<35)return b=a(this).attr("data-full").split("-"),t(c=O(b[0],b[1]-1,b[2]),p)||(N=!0,d.setVal(c)),!1})}function u(){m&&D&&m.hide(),C=null,D=!1}function ae(b){0==a(b.target).closest(".mbsc-cal-day").length&&u()}function G(){u(),d.redraw()}function ad(d){var a=b.getYear(d),c=b.getMonth(d),i=b.getDay(d),g,j;(h=d,"day"==f)?k=b.getDate(a,c,i+e-1):"week"==f?(j=h.getDay(),g=i+b.firstDay-(0<b.firstDay-j?7:0)-j,h=b.getDate(a,c,g),k=b.getDate(a,c,g+7*e-1)):"month"==f?(h=b.getDate(a,c,1),k=b.getDate(a,c+e,0)):"year"==f&&(h=b.getDate(a,0,1),k=b.getDate(a+e,0,0))}function V(c,d){if(r&&!N){var b=a('.mbsc-event-day[data-full="'+ap(c)+'"]',q);b.length&&(y++,dy(P,b.parent()[0].offsetTop,d,function(){setTimeout(function(){y--},150)}))}}function L(a,b){a&&n("onPageChange",{firstDay:h,lastDay:k}),b||n("onPageLoading",{firstDay:h,lastDay:k}),n("onPageLoaded",{firstDay:h,lastDay:k})}var x,B,q,ab,p,h,k,C,P,v,D,S,R,z,f,e,K,o,m,N,aa,F,E,T,r,I,W,Q,l,s,$,J,ai=this,ag=c({},d.settings),b=c(d.settings,ci,ag,cj,ah),y=0,Y=0,i=c(!0,[],b.data),U=!0,n=d.trigger;return b.data=i,a.each(i,function(b,a){void 0===a._id&&(a._id=Y++)}),Q=b.view,l=Q.calendar,s=Q.eventList,$=b.months,J=b.weeks,z=l?("week"==l.type?J=l.size||1:l.size&&($=l.size),!1):!(J=0),s&&(f=s.type,e=s.size||1),K=l&&l.labels,T=s&&s.scrollable,r=Q.eventList,I=void 0===b.eventBubble?l&&l.popover:b.eventBubble,b.weeks=J,b.months=$,x=bj.call(this,d),d._onSelectShow=function(){u()},d._onGenMonth=function(a,b){v=d._prepareObj(i,a,b),d._labels=K?v:null},d._onRefresh=function(a){aa=!0,E=F=null,z&&L(!1,a)},d._onSetDate=function(a,b){p=a,z?N||(ad(a),L(!0)):b||S||(r&&"day"==f&&Z(a,a,v),!I&&!W||R||ac(),V(a)),R=W=N=!1},d._getDayProps=function(d){var a=v[d],c={events:a};return b.marked||b.labels||K||(a?(c.background=a[0]&&a[0].background,c.marked=a,c.markup=b.showEventCount?'<div class="mbsc-cal-txt">'+a.length+" "+(1<a.length?b.eventsText:b.eventText)+"</div>":'<div class="mbsc-cal-marks"><div class="mbsc-cal-mark"></div></div>'):c.markup=b.showEventCount?'<div class="mbsc-cal-txt-ph"></div>':""),c},d.addEvent=function(b){var d=[];return b=c(!0,[],a.isArray(b)?b:[b]),a.each(b,function(b,a){void 0===a._id&&(a._id=Y++),i.push(a),d.push(a._id)}),G(),d},d.updateEvent=function(b){a.each(i,function(a,c){if(c._id===b._id)return i.splice(a,1,b),!1}),G()},d.removeEvent=function(b){b=a.isArray(b)?b:[b],a.each(b,function(c,b){a.each(i,function(a,c){if(c._id===b)return i.splice(a,1),!1})}),G()},d.getEvents=function(a){var b;return a?(a.setHours(0,0,0,0),(b=d._prepareObj(i,a,a))[a]?_(b[a]):[]):c(!0,[],i)},d.setEvents=function(e){var d=[];return b.data=i=c(!0,[],e),a.each(i,function(b,a){void 0===a._id&&(a._id=Y++),d.push(a._id)}),G(),d},d.navigate=function(a,b,c){o=c?{d:a}:null,d.setVal(a,!0,!0,!1,b?200:0)},c({},x,{multiLabel:K,headerText:!1,buttons:"inline"!==b.display?["close"]:b.buttons,compClass:"mbsc-ev-cal mbsc-calendar mbsc-dt mbsc-sc",formatDuration:function(h,i){var a=b.labelsShort,g=i-h,e=Math.abs(g)/1e3,f=e/60,d=f/60,c=d/24,j=c/365;return e<45&&Math.round(e)+" "+a[5].toLowerCase()||f<45&&Math.round(f)+" "+a[4].toLowerCase()||d<24&&Math.round(d)+" "+a[3].toLowerCase()||c<30&&Math.round(c)+" "+a[2].toLowerCase()||c<365&&Math.round(c/30)+" "+a[1].toLowerCase()||Math.round(j)+" "+a[0].toLowerCase()},onMarkupReady:function(c,d){B=a(c.target),p=d.getDate(!0),r&&((q=a('<div class="mbsc-lv-cont mbsc-lv-'+b.theme+(b.baseTheme?" mbsc-lv-"+b.baseTheme:"")+(T?" mbsc-event-list-h":"")+' mbsc-event-list"></div>').appendTo(a(".mbsc-fr-w",B))).on("scroll",bo(af)),P=q[0]),x.onMarkupReady.call(this,c),ab=a(".mbsc-cal-month",B),D=!1,ad(p),r&&z&&(L(),aO(a(".mbsc-cal-btn",B),function(i,a){var c=b.getYear(h),d=b.getMonth(h),g=b.getDay(h);"day"==f?(h=b.getDate(c,d,g+a*e),k=b.getDate(c,d,g+(a+1)*e-1)):"week"==f?(h=b.getDate(c,d,g+a*e*7),k=b.getDate(c,d,g+(a+1)*e*7-1)):"month"==f?(h=b.getDate(c,d+a*e,1),k=b.getDate(c,d+(a+1)*e,0)):"year"==f&&(h=b.getDate(c+a*e,0,1),k=b.getDate(c+(a+1)*e,0,0)),L(!0)},200)),a(document).on("click",ae)},onDayChange:function(d){var c=d.target,e=c!==C;u(),e&&(W=!1!==I&&a(".mbsc-cal-txt-more",c).length,o={d:d.date,cell:b.outerMonthChange&&a(c).hasClass("mbsc-cal-day-diff")?null:c,events:d.events})},onLabelTap:function(a){a.label&&(n("onEventSelect",{domEvent:a.domEvent,event:a.label,date:a.date}),R=!0)},onPageChange:function(a){u(),S=!0,d._isSetDate||d.setVal(a.firstDay)},onPageLoaded:function(g){var a=g.firstDay,c=g.lastDay;r&&(z?F&&E&&t(F,a)&&t(E,c)||(Z(F=a,E=c),function(d,g){var a,k=(b.dateWheels||b.dateFormat).search(/m/i),l=(b.dateWheels||b.dateFormat).search(/y/i),c=b.getYear(d),h=b.getMonth(d),i=b.getYear(g),m=b.getMonth(g);"day"==f?a=j(b.dateFormat,d,b)+(1<e?" - "+j(b.dateFormat,g,b):""):"week"==f?a=j(b.dateFormat,d,b)+" - "+j(b.dateFormat,g,b):"month"==f?a=1==e?l<k?c+" "+b.monthNames[h]:b.monthNames[h]+" "+c:l<k?c+" "+b.monthNamesShort[h]+" - "+i+" "+b.monthNamesShort[m]:b.monthNamesShort[h]+" "+c+" - "+b.monthNamesShort[m]+" "+i:"year"==f&&(a=c+(1<e?" - "+i:"")),ab.html(a)}(a,c)):(c="month"==f?b.getDate(b.getYear(a),b.getMonth(a)+e,0):"week"==f?b.getDate(b.getYear(a),b.getMonth(a),b.getDay(a)+7*e-1):a=d.getVal(!0),Z(a,c,v)),U||t(p,a)||(V(p,aa),aa=!1)),I&&ac(),S=!1},onPosition:function(c){if(x.onPosition.call(this,c),m&&m.position(),r&&T){q.addClass("mbsc-event-list-h");var a=function(a){var b=getComputedStyle(a);return a.innerHeight||a.clientHeight-parseFloat(b.paddingTop)-parseFloat(b.paddingBottom)}("inline"==b.display?ai.parentNode:window)-c.popup.offsetHeight;P.style.height=200<a?a+"px":"",q.removeClass("mbsc-event-list-h"),U&&a&&(V(p,!0),U=!1)}},onHide:function(){x.onHide.call(this),m&&m.destroy(),a(document).off("click",ae)}})},h("eventcalendar",k),cl=q&&!!window.Promise,D=[],U=[];function bK(a){D.length||a.show(),D.push(a)}function a_(d,a,b,e){return c({display:a.display||"center",cssClass:"mbsc-alert",okText:a.okText,cancelText:a.cancelText,context:a.context,theme:a.theme,closeOnOverlayTap:!1,onBeforeClose:function(){d.shift()},onHide:function(d,c){b&&b(c._resolve),a.callback&&a.callback(c._resolve),c&&c.destroy(),D.length?D[0].show():U.length&&U[0].show(!1,!0)}},e)}function bF(a){return(a.title?"<h2>"+a.title+"</h2>":"")+"<p>"+(a.message||"")+"</p>"}function dt(a,b,c){bK(new w(a,a_(D,b,c)))}function ds(b,c,d){var a=new w(b,a_(D,c,d,{buttons:["cancel","ok"],onSet:function(){a._resolve=!0}}));a._resolve=!1,bK(a)}function dr(c,d,e){var a,b=new w(c,a_(D,d,e,{buttons:["cancel","ok"],onMarkupReady:function(d,b){var c=b.settings;b._markup.find("label").addClass("mbsc-"+c.theme+(c.baseTheme?" mbsc-"+c.baseTheme:"")),a=b._markup.find("input")[0],setTimeout(function(){a.focus(),a.setSelectionRange(0,a.value.length)},300)},onSet:function(){b._resolve=a.value}}));b._resolve=null,bK(b)}function cu(d,b,e,f,g){var c;!function(a){var b=U.length;U.push(a),D.length||(b?U[0].hide():a.show(!1,!0))}(new w(d,a_(U,b,e,{display:b.display||"bottom",animate:g,cssClass:(f||"mbsc-snackbar")+(b.color?" mbsc-"+b.color:""),scrollLock:!1,focusTrap:!1,buttons:[],onMarkupReady:function(c,b){var a=b.settings;b._markup.find("button").addClass("mbsc-"+a.theme+(a.baseTheme?" mbsc-"+a.baseTheme:""))},onShow:function(e,d){aw=d,!1!==b.duration&&(c=setTimeout(function(){d&&d.hide()},b.duration||3e3)),b.button&&d.tap(a(".mbsc-snackbar-btn",e.target),function(){d.hide(),b.button.action&&b.button.action.call(this)})},onClose:function(){aw=null,clearTimeout(c)}})))}function dz(a,b,c){cu(a,b,c,"mbsc-toast","fade")}function ax(a,b,c){var d;return cl?d=new Promise(function(d){a(b,c,d)}):a(b,c),d}b.alert=function(a){var b=document.createElement("div");return b.innerHTML=bF(a),ax(dt,b,a)},b.confirm=function(a){var b=document.createElement("div");return b.innerHTML=bF(a),ax(ds,b,a)},b.prompt=function(a){var b=document.createElement("div");return b.innerHTML=bF(a)+'<label class="mbsc-input">'+(a.label?'<span class="mbsc-label">'+a.label+"</span>":"")+'<input class="mbsc-control" tabindex="0" type="'+(a.inputType||"text")+'" placeholder="'+(a.placeholder||"")+'" value="'+(a.value||"")+'"></label>',ax(dr,b,a)},b.snackbar=function(b){var c=document.createElement("div"),a=b.button;return c.innerHTML='<div class="mbsc-snackbar-cont"><div class="mbsc-snackbar-msg">'+(b.message||"")+"</div>"+(a?'<button class="mbsc-snackbar-btn mbsc-btn mbsc-btn-flat">'+(a.icon?'<span class="mbsc-ic '+(a.text?"mbsc-btn-ic ":"")+"mbsc-ic-"+a.icon+'"></span>':"")+(a.text||"")+"</button>":"")+"</div>",ax(cu,c,b)},b.toast=function(a){var b=document.createElement("div");return b.innerHTML='<div class="mbsc-toast-msg">'+(a.message||"")+"</div>",ax(dz,b,a)},b.notification={dismiss:function(){aw&&aw.hide()}};function bt(i,j){var c,f="",e=a(i),g={},d=this;function h(){e.removeClass("mbsc-no-touch")}C.call(this,i,j,!0),d.refresh=function(a){c.enhance&&ch(e,g,c,a)},d._init=function(){b.themes.form[c.theme]||(c.theme="mobiscroll"),e.hasClass("mbsc-form")||e.on("touchstart",h).show(),f&&e.removeClass(f),f="mbsc-form mbsc-no-touch mbsc-"+c.theme+(cy?" mbsc-form-hb":"")+(c.baseTheme?" mbsc-"+c.baseTheme:"")+(c.rtl?" mbsc-rtl":" mbsc-ltr")+("box"==c.inputStyle?" mbsc-form-box":"")+("outline"==c.inputStyle?" mbsc-form-outline":""),e.addClass(f).removeClass("mbsc-cloak"),d.refresh()},d._destroy=function(){for(var a in e.removeClass(f).off("touchstart",h),g)g[a].destroy()},d.controls=g,c=d.settings,d.init()}cy="ios"==m&&7<x,bt.prototype={_hasDef:!0,_hasTheme:!0,_hasLang:!0,_class:"form",_defaults:{tap:Y,stopProp:!0,rtl:!1,enhance:!0}},o("[mbsc-enhance],[mbsc-form]",l.Form=bt,!0);function ba(f,g){var c="",e=a(f),d=this,b=d.settings;C.call(this,f,g,!0),d._init=function(){var g=b.context,d=a(g),h=d.find(".mbsc-ms-top .mbsc-ms"),i=d.find(".mbsc-ms-bottom .mbsc-ms"),f={};"body"==g?a("body,html").addClass("mbsc-page-ctx"):d.addClass("mbsc-page-ctx"),c&&e.removeClass(c),h.length&&(f.paddingTop=h[0].offsetHeight),i.length&&(f.paddingBottom=i[0].offsetHeight),c="mbsc-page mbsc-"+b.theme+(b.baseTheme?" mbsc-"+b.baseTheme:"")+(b.rtl?" mbsc-rtl":" mbsc-ltr"),e.addClass(c).removeClass("mbsc-cloak").css(f)},d._destroy=function(){e.removeClass(c)},b=d.settings,d.init()}ba.prototype={_hasDef:!0,_hasTheme:!0,_hasLang:!0,_class:"page",_defaults:{context:"body"}},l.Page=ba,b.themes.page.mobiscroll={},o("[mbsc-page]",ba),h("page",ba,!1),h("form",bt,!1),h("progress",av,!1),h("slider",ak,!1),h("stepper",aZ,!1),h("switch",aW,!1),h("rating",aT,!1);function cA(d){var g,e,j,v,w=c({},d.settings),b=c(d.settings,cB,w),t=b.layout||(/top|bottom/.test(b.display)?"liquid":""),l="liquid"==t,x=b.readonly,i=a(this),p=this.id+"_dummy",f=0,o=[],h=b.wheelArray||function f(c){var e=[],g=1<c.length?c:c.children(b.itemSelector);return g.each(function(l){var c=a(this),g=c.clone(),j,k,i,h;g.children("ul,ol").remove(),g.children(b.itemSelector).remove(),j=d._processMarkup?d._processMarkup(g):g.html().replace(/^\s\s*/,"").replace(/\s\s*$/,""),k=!!c.attr("data-invalid"),i={key:void 0===c.attr("data-val")||null===c.attr("data-val")?l:c.attr("data-val"),value:j,invalid:k,children:null},h="li"===b.itemSelector?c.children("ul,ol"):c.children(b.itemSelector),h.length&&(i.children=f(h)),e.push(i)}),e}(i),r=function(e){for(var a,c=[],d=e,b=!0,f=0;b;)a=n(d),c[f++]=a.key,(b=a.children)&&(d=b);return c}(h);function y(g,e,f){for(var c,a=0,b=f,d=[],h;a<e;){h=g[a];for(c in b)if(b[c].key==h){b=b[c].children;break}a++}for(a=0;a<b.length;)b[a].invalid&&d.push(b[a].key),a++;return d}function k(j,i,f){var c,a,d=0,g=!0,e=[[]],b=h;if(i)for(a=0;a<i;a++)l?e[0][a]={}:e[a]=[{}];for(;g;){for(l?e[0][d]=u(b,d):e[d]=[u(b,d)],a=0,c=null;a<b.length&&!c;)b[a].key==j[d]&&(void 0!==f&&d<=f||void 0===f)&&(c=b[a]),a++;(c=c||n(b))&&c.children?(b=c.children,d++):g=!1}return e}function n(a,d){if(!a)return!1;for(var c,b=0;b<a.length;)if(!(c=a[b++]).invalid)return d?b-1:c;return!1}function u(c,d){for(var e={data:[],label:b.labels&&b.labels[d]?b.labels[d]:d},a=0;a<c.length;)e.data.push({value:c[a].key,display:c[a].value}),a++;return e}function q(b){d._isVisible&&a(".mbsc-sc-whl-w",d._markup).css("display","").slice(b).hide()}function m(f,j){for(var b,e,d,c=0,a=h,g=!0,i=[];g;){if(void 0!==f[c]&&c<=j)for(d=0,e=f[c],b=void 0;d<a.length&&void 0===b;)a[d].key!=f[c]||a[d].invalid||(b=d),d++;else e=a[b=n(a,!0)]&&a[b].key;i[c]=e,c++,g=!!a[b]&&a[b].children,a=a[b]&&a[b].children}return{lvl:c,nVector:i}}function s(c,g,e){var a,f,b=(g||0)+1,h=[],i={};for(f=k(c,null,g),a=0;a<c.length;a++)d._tempWheelArray[a]=c[a]=e.nVector[a]||0;for(;b<e.lvl;)i[b]=l?f[0][b]:f[b][0],h.push(b++);q(e.lvl),o=c.slice(0),h.length&&(j=!0,d.changeWheel(i))}return function d(b,c){var a;for(f=f<c?c:f,a=0;a<b.length;a++)b[a].children&&d(b[a].children,c+1)}(h,1),v=k(r,f),a("#"+p).remove(),b.input?e=a(b.input):b.showInput&&(e=a('<input type="text" id="'+p+'" value="" class="'+b.inputClass+'" placeholder="'+(b.placeholder||"")+'" readonly />').insertBefore(i)),e&&d.attachShow(e),b.wheelArray||i.hide(),{wheels:v,anchor:e,layout:t,headerText:!1,setOnTap:1==f,formatValue:function(a){return void 0===g&&(g=m(a,a.length).lvl),a.slice(0,g).join(" ")},parseValue:function(a){return a?(a+"").split(" "):(b.defaultValue||r).slice(0)},onBeforeShow:function(){var a=d.getArrayVal(!0);o=a.slice(0),b.wheels=k(a,f,f),j=!0},onWheelGestureStart:function(a){b.readonly=function(b,c){for(var a=[];b;)a[--b]=!0;return a[c]=!1,a}(f,a.index)},onWheelAnimationEnd:function(f){var a=f.index,c=d.getArrayVal(!0),e=m(c,a);g=e.lvl,b.readonly=x,c[a]!=o[a]&&s(c,a,e)},onFill:function(a){g=void 0,e&&e.val(a.valueText)},validate:function(c){var a=c.values,d=c.index,b=m(a,a.length);return g=b.lvl,void 0===d&&(q(b.lvl),j||s(a,d,b)),j=!1,{disabled:function(c,d,e){for(var a=0,b=[];a<c;)b[a]=y(e,a,d),a++;return b}(g,h,a)}},onDestroy:function(){e&&a("#"+p).remove(),i.show()}}}cB={invalid:[],showInput:!0,inputClass:"",itemSelector:"li"},i.image=function(b){return b.settings.enhance&&(b._processMarkup=function(b){var c=b.attr("data-icon");return b.children().each(function(c,b){(b=a(b)).is("img")?a('<div class="mbsc-img-c"></div>').insertAfter(b).append(b.addClass("mbsc-img")):b.is("p")&&b.addClass("mbsc-img-txt")}),c&&b.prepend('<div class="mbsc-ic mbsc-ic-'+c+'"></div'),b.html('<div class="mbsc-img-w">'+b.html()+"</div>"),b.html()}),cA.call(this,b)},h("image",k);function cC(cA,cD){var L,aD,ap,j,ba,b$,cz,h,D,x,aZ,aX,cy,m,ae,cx,aC,A,aa,G,M,ac,cw,cv,ct,cs,p,aB,aQ,R,bB,bA,H,cr,cp,X,bz,aP,cn,cm,by,bx,ax,z,o,bp,aN,bv,an,f,q,k,ab,I,bu,aE,O,aL,aI,aH,bg,bR,F,U,au,bs,ag,cl,aJ,aK,cj,br,K,u,V,aj,aR,ch,aT,b,ad,bt,af,bn,bl,ai,cg,ce,bc,be,Y,T,bi,cd,bj,bb,a_,l,aq,aM,aG,i,av,aw,bw,g=this,v=cA,w=a(v),S=0,al=0,t=0,aS={},bE={},r={},a$,ca,ak,bK,aW;function cc(){aJ=aR=!1,cg=j=0,ce=new Date,an=x.width(),cy=az(x),k=cy.index(f),q=f[0].offsetHeight,t=f[0].offsetTop,l=aq[f.attr("data-type")||"defaults"],ai=l.stages}function cB(c){var d;"touchstart"===c.type&&(h.removeClass("mbsc-no-touch"),aK=!0,clearTimeout(cj)),!N(c,this)||L||S||J||a$||(bp=!(ba=L=!0),bv="touchstart"===c.type,bc=e(c,"X"),be=e(c,"Y"),aa=A=0,f=a(this),d=f,cc(),bj=l.actionable||f.hasClass("mbsc-lv-parent")||f.hasClass("mbsc-lv-back"),bu=f.offset().top,bj&&(ap=setTimeout(function(){d.addClass(bv?W:""),p("onItemActivate",{target:d[0],domEvent:c})},120)),g.sortable&&!f.hasClass("mbsc-lv-back")&&(g.sortable.group||(bs=f.nextUntil(".mbsc-lv-gr-title").filter(".mbsc-lv-item"),br=f.prevUntil(".mbsc-lv-gr-title").filter(".mbsc-lv-item")),aH=(g.sortable.group?x.children(I).eq(0):br.length?br.eq(-1):f)[0].offsetTop-t,aI=(g.sortable.group?x.children(I).eq(-1):bs.length?bs.eq(-1):f)[0].offsetTop-t,g.sortable.handle?a(c.target).hasClass("mbsc-lv-handle")&&(clearTimeout(ap),"Moz"===n?(c.preventDefault(),bP()):a_=setTimeout(function(){bP()},100)):a_=setTimeout(function(){aB.appendTo(f),aB[0].style[n+"Animation"]="mbsc-lv-fill "+(b.sortDelay-100)+"ms linear",clearTimeout(cv),clearTimeout(ap),ba=!1,a_=setTimeout(function(){aB[0].style[n+"Animation"]="",bP()},b.sortDelay-80)},80)),"mousedown"==c.type&&a(document).on("mousemove",bI).on("mouseup",aU))}function bI(d){var b=!1,h=!0,n=j,m,k,a,l;L&&((ct=e(d,"X"),cs=e(d,"Y"),A=ct-bc,aa=cs-be,clearTimeout(cv),ac||T||ad||f.hasClass("mbsc-lv-back")||(10<Math.abs(aa)?(ad=!0,aU(c({},d,{type:"mousemove"==d.type?"mouseup":"touchend"})),clearTimeout(ap)):7<Math.abs(A)&&bY()),T)?(d.preventDefault(),j=A/an*100,bM(n)):ac?(d.preventDefault(),k=i.scrollTop(),a=Math.max(aH,Math.min(aa+aw,aI)),l=H?bu-bw+k-aw:bu,av+k<l+a+q?(i.scrollTop(l+a-av+q),m=!0):l+a<k&&(i.scrollTop(l+a),m=!0),m&&(aw+=i.scrollTop()-k),U&&(g.sortable.multiLevel&&F.hasClass("mbsc-lv-parent")?U<t+q/4+a?b=!0:U<t+q-q/4+a&&(G=F.addClass("mbsc-lv-item-hl"),h=!1):U<t+q/2+a&&(F.hasClass("mbsc-lv-back")?g.sortable.multiLevel&&(M=F.addClass("mbsc-lv-item-hl"),h=!1):b=!0),b&&(K.insertAfter(F),F=aO(u=F,"next"),V=U,U=F.length&&F[0].offsetTop,D++)),!b&&V&&(g.sortable.multiLevel&&u.hasClass("mbsc-lv-parent")?t+q-q/4+a<V?b=!0:t+q/4+a<V&&(G=u.addClass("mbsc-lv-item-hl"),h=!1):t+q/2+a<V&&(u.hasClass("mbsc-lv-back")?g.sortable.multiLevel&&(M=u.addClass("mbsc-lv-item-hl"),h=!1):b=!0),b&&(K.insertBefore(u),u=aO(F=u,"prev"),U=V,V=u.length&&u[0].offsetTop+u[0].offsetHeight,D--)),h&&(G&&(G.removeClass("mbsc-lv-item-hl"),G=!1),M&&(M.removeClass("mbsc-lv-item-hl"),M=!1)),b&&p("onSortChange",{target:f[0],index:D}),cb(f,a),p("onSort",{target:f[0],index:D})):(5<Math.abs(A)||5<Math.abs(aa))&&bG())}function aU(e){var h,c,d,i=f;L&&(L=!1,bG(),"mouseup"==e.type&&a(document).off("mousemove",bI).off("mouseup",aU),ad||(cj=setTimeout(function(){aK=!1},300)),(T||ad||ac)&&(aJ=!0),T?bV():ac?(d=x,G?(ah(f.detach()),c=r[G.attr("data-ref")],D=az(c.child).length,G.removeClass("mbsc-lv-item-hl"),b.navigateOnDrop?bD(G,function(){g.add(null,f,null,null,G,!0),aA(f),ay(f,k,d,!0)}):(g.add(null,f,null,null,G,!0),ay(f,k,d,!0))):M?(ah(f.detach()),c=r[M.attr("data-back")],D=az(c.parent).index(c.item)+1,M.removeClass("mbsc-lv-item-hl"),b.navigateOnDrop?bD(M,function(){g.add(null,f,D,null,x,!0),aA(f),ay(f,k,d,!0)}):(g.add(null,f,D,null,c.parent,!0),ay(f,k,d,!0))):(h=K[0].offsetTop-t,cb(f,h,6*Math.abs(h-Math.max(aH,Math.min(aa+aw,aI))),function(){ah(f),f.insertBefore(K),ay(f,k,d,D!==k)})),ac=!1):!ad&&Math.abs(A)<5&&Math.abs(aa)<5&&(bp=!0,"touchend"===e.type&&b.tap&&bQ(e.target,aY(a(e.target)),e)),clearTimeout(ap),setTimeout(function(){i.removeClass(W),p("onItemDeactivate",{target:i[0]})},100),ad=!1,m=null)}function cC(b){var c;bp&&(c="true"==f.attr("data-selected"),l.tap&&l.tap.call(v,{target:f,index:k,domEvent:b},g),bj&&!f.hasClass(W)&&(f.addClass(bv?W:""),p("onItemActivate",{target:f[0],domEvent:b})),bt&&(bR?c?aV(f):bJ(f):(aV(a(I,h).filter("."+am)),bJ(f))),!1!==p("onItemTap",{target:f[0],index:k,domEvent:b,selected:c})&&bD(f))}function bY(){(T=ar(l.swipe,{target:f[0],index:k,direction:0<A?"right":"left"}))&&(bG(),clearTimeout(ap),l.actions?(aD=cq(l,A),bg.html(l.icons).show().children().css("width",aD+"%"),z.hide(),a(".mbsc-lv-ic-m",o).removeClass("mbsc-lv-ic-disabled"),a(l.leftMenu).each(bX),a(l.rightMenu).each(bX)):(z.show(),bg.hide(),ae=l.start,m=ai[ae],aj=ai[ae-1],au=ai[ae+1]),f.addClass("mbsc-lv-item-swiping").removeClass(W),bb.css("line-height",q+"px"),o.css({top:t,height:q,backgroundColor:co(A)}).addClass("mbsc-lv-stage-c-v").appendTo(x.parent()),b.iconSlide&&f.append(z),p("onSlideStart",{target:f[0],index:k}))}function bM(c){var a=!1;aT||(l.actions?o.attr("class","mbsc-lv-stage-c-v mbsc-lv-stage-c mbsc-lv-"+(j<0?"right":"left")):(aj&&(j<0?j<=aj.percent:j<m.percent)?(au=m,m=aj,aj=ai[--ae-1],a=!0):au&&(j<0?j>m.percent:j>=au.percent)&&(aj=m,m=au,au=ai[++ae+1],a=!0),m&&(!a&&0<j!=c<=0||bh(m,b.iconSlide),a&&p("onStageChange",{target:f[0],index:k,stage:m}))),af||(aT=!0,ch=E(b_)))}function bV(c){var e,g,d=!1;bd(ch),aT=!1,af||b_(),l.actions?10<Math.abs(j)&&aD&&(at(f,j<0?-aD:aD,200),J=d=!0,b$=f,cz=k,a(document).on("touchstart.mbsc-lv-conf mousedown.mbsc-lv-conf",function(a){a.preventDefault(),bf(f,!0,c)})):j&&(b.quickSwipe&&!af&&(e=(g=new Date-ce)<300&&50<A,g<300&&A<-50?(aR=!0,bh(m=l.left,b.iconSlide)):e&&(aR=!0,bh(m=l.right,b.iconSlide))),m&&m.action&&(ar(m.disabled,{target:f[0],index:k})||(d=!0,(J=af||ar(m.confirm,{target:f[0],index:k}))?(at(f,(j<0?-1:1)*z[0].offsetWidth*100/an,200,!0),bZ(m,f,k,!1,c)):bL(m,f,k,c)))),d||bf(f,!0,c),T=!1}function bP(){M=G=!(ac=!0),aw=0,D=k,b.vibrate&&bq(),F=aO(f,"next"),U=F.length&&F[0].offsetTop,u=aO(f,"prev"),V=u.length&&u[0].offsetTop+u[0].offsetHeight,K.height(q).insertAfter(f),f.css({top:t}).addClass("mbsc-lv-item-dragging").removeClass(W).appendTo(cw),p("onSortStart",{target:f[0],index:D})}function ay(a,c,d,e){a.removeClass("mbsc-lv-item-dragging"),K.remove(),p("onSortEnd",{target:a[0],index:D}),b.vibrate&&bq(),e&&(g.addUndoAction(function(b){g.move(a,c,null,b,d,!0)},!0),p("onSortUpdate",{target:a[0],index:D}))}function bS(){aK||(clearTimeout(cm),J&&a(document).trigger("touchstart"),bz&&(g.close(X,aP),bz=!1,X=null))}function aF(){clearTimeout(cx),cx=setTimeout(function(){av=i[0].innerHeight||i.innerHeight(),bw=H?i.offset().top:0,L&&(t=f[0].offsetTop,q=f[0].offsetHeight,o.css({top:t,height:q}))},200)}function bU(a){aJ&&(a.stopPropagation(),a.preventDefault(),aJ=!1)}function bN(){aN||(clearTimeout(cl),cl=setTimeout(function(){var a=H?i[0].getBoundingClientRect().top+i.innerHeight():window.innerHeight,b=aL[0].getBoundingClientRect().top-3<a;!aN&&b&&p("onListEnd")},250))}function bW(){if(ac||!L){var c,e=i.scrollTop(),d=w.offset().top,f=w[0].offsetHeight,b=H?i.offset().top:e;a(".mbsc-lv-gr-title",w).each(function(e,d){a(d).offset().top<b&&(c=d)}),d<b&&b<d+f?R.show().empty().append(a(c).clone()):R.hide()}}function bX(c,b){ar(b.disabled,{target:f[0],index:k})&&a(".mbsc-ic-"+b.icon,o).addClass("mbsc-lv-ic-disabled")}function bL(d,c,e,h){var f,i={icon:"undo2",text:b.undoText,action:function(){g.undo()}};d.undo&&(g.startActionTrack(),a.isFunction(d.undo)&&g.addUndoAction(function(){d.undo.call(v,{target:c[0],index:e},g)}),aM=c.attr("data-ref")),f=d.action.call(v,{target:c[0],index:e},g),d.undo?(g.endActionTrack(),!1!==f&&at(c,+c.attr("data-pos")<0?-100:100,200),K.height(q).insertAfter(c),c.css("top",t).addClass("mbsc-lv-item-undo"),bg.hide(),z.show(),o.append(z),bh(i),bZ(i,c,e,!0,h)):bf(c,f,h)}function bZ(i,b,h,d,f){var g,c;J=!0,a(document).off(".mbsc-lv-conf").on("touchstart.mbsc-lv-conf mousedown.mbsc-lv-conf",function(a){a.preventDefault(),d&&cf(b),bf(b,!0,f)}),aC||z.off(".mbsc-lv-conf").on("touchstart.mbsc-lv-conf mousedown.mbsc-lv-conf",function(a){a.stopPropagation(),g=e(a,"X"),c=e(a,"Y")}).on("touchend.mbsc-lv-conf mouseup.mbsc-lv-conf",function(a){a.preventDefault(),"touchend"===a.type&&Q(),Math.abs(e(a,"X")-g)<10&&Math.abs(e(a,"Y")-c)<10&&(bL(i,b,h,f),d&&(aG=null,cf(b)))})}function b_(){at(f,cg+100*A/an),aT=!1}function bf(b,d,c){a(document).off(".mbsc-lv-conf"),z.off(".mbsc-lv-conf"),!1!==d?at(b,0,"0"!==b.attr("data-pos")?200:0,!1,function(){bk(b,c),ah(b)}):bk(b,c),J=!1}function at(b,a,c,e,d){a=Math.max("right"==T?0:-100,Math.min(a,"left"==T?0:100)),Y=b[0].style,b.attr("data-pos",a),Y[n+"Transform"]="translate3d("+(e?an*a/100+"px":a+"%")+",0,0)",Y[n+"Transition"]=y+"transform "+(c||0)+"ms",d&&(S++,setTimeout(function(){d(),S--},c)),j=a}function cb(d,a,b,c){a=Math.max(aH,Math.min(a,aI)),(Y=d[0].style)[n+"Transform"]="translate3d(0,"+a+"px,0)",Y[n+"Transition"]=y+"transform "+(b||0)+"ms ease-out",c&&(S++,setTimeout(function(){c(),S--},b))}function bG(){clearTimeout(a_),!ba&&g.sortable&&(ba=!0,aB.remove())}function bh(a,d){var c=ar(a.text,{target:f[0],index:k})||"";ar(a.disabled,{target:f[0],index:k})?o.addClass("mbsc-lv-ic-disabled"):o.removeClass("mbsc-lv-ic-disabled"),o.css("background-color",a.color||(0===a.percent?co(j):bm)),z.attr("class","mbsc-lv-ic-c mbsc-lv-ic-"+(d?"move-":"")+(j<0?"right":"left")),ax.attr("class"," mbsc-lv-ic-s mbsc-lv-ic mbsc-ic mbsc-ic-"+(a.icon||"none")),bb.attr("class","mbsc-lv-ic-text"+(a.icon?"":" mbsc-lv-ic-text-only")+(c?"":" mbsc-lv-ic-only")).html(c||"&nbsp;"),b.animateIcons&&(aR?ax.addClass("mbsc-lv-ic-v"):setTimeout(function(){ax.addClass("mbsc-lv-ic-a")},10))}function bk(a,b){L||(ax.attr("class","mbsc-lv-ic-s mbsc-lv-ic mbsc-ic mbsc-ic-none"),o.attr("style","").removeClass("mbsc-lv-stage-c-v"),bb.html("")),o.removeClass("mbsc-lv-left mbsc-lv-right"),a&&(p("onSlideEnd",{target:a[0],index:k}),b&&b())}function cf(a){a.css("top","").removeClass("mbsc-lv-item-undo"),aG?g.animate(K,"collapse",function(){K.remove()}):K.remove(),bk(),aG=aM=null}function ah(a){(Y=a[0].style)[n+"Transform"]="",Y[n+"Transition"]="",Y.top="",a.removeClass("mbsc-lv-item-swiping")}function ar(b,c){return a.isFunction(b)?b.call(this,c,g):b}function ci(a){return bt&&!a.hasClass("mbsc-lv-parent")&&!a.hasClass("mbsc-lv-back")}function bT(c){var d=c.attr("data-ref"),e=c.attr("data-role"),j=aq[c.attr("data-type")||"defaults"],f=ci(c)&&"true"==c.attr("data-selected"),h,i;d||(d=ao++,c.attr("data-ref",d)),r[d]={item:c,child:c.children(O),parent:c.parent(),ref:c.parent()[0]===v?null:c.parent().parent().attr("data-ref")},c.addClass("list-divider"==e?"mbsc-lv-gr-title":"mbsc-lv-item"+(j.actionable?" mbsc-lv-item-actionable":"")+(f?" "+am:"")),c.attr("aria-selected",f?"true":"false"),g.sortable.handle&&"list-divider"!=e&&!c.children(".mbsc-lv-handle-c").length&&c.append(cr),b.enhance&&!c.hasClass("mbsc-lv-item-enhanced")&&(h=c.attr("data-icon"),i=c.find("img").eq(0).addClass("mbsc-lv-img"),i.is(":first-child")?c.addClass("mbsc-lv-img-"+(b.rtl?"right":"left")):i.length&&c.addClass("mbsc-lv-img-"+(b.rtl?"left":"right")),c.addClass("mbsc-lv-item-enhanced").children().each(function(c,b){(b=a(b)).is("p, h1, h2, h3, h4, h5, h6")&&b.addClass("mbsc-lv-txt")}),h&&c.addClass("mbsc-lv-item-ic-"+(c.attr("data-icon-align")||(b.rtl?"right":"left"))).append('<div class="mbsc-lv-item-ic mbsc-ic mbsc-ic-'+h+'"></div>'))}function ck(b){a(I,b).not(".mbsc-lv-back").each(function(){bT(a(this))}),a(O,b).not(".mbsc-lv").addClass("mbsc-lv").prepend(by).parent().addClass("mbsc-lv-parent mbsc-lv-item-actionable").prepend(bx),a(".mbsc-lv-back",b).each(function(){a(this).attr("data-back",a(this).parent().parent().attr("data-ref"))})}function az(a){return a.children(I).not(".mbsc-lv-back").not(".mbsc-lv-removed").not(".mbsc-lv-ph")}function $(b){return"object"!==Z(b)&&(b=a(I,h).filter('[data-id="'+b+'"]')),a(b)}function aO(a,b){for(a=a[b]();a.length&&(!a.hasClass("mbsc-lv-item")||a.hasClass("mbsc-lv-ph")||a.hasClass("mbsc-lv-item-dragging"));){if(!g.sortable.group&&a.hasClass("mbsc-lv-gr-title"))return!1;a=a[b]()}return a}function co(a){return(0<a?l.right:l.left).color||bm}function _(a){return s(a)?a+"":0}function cq(a,c){return+(c<0?_((a.actionsWidth||0).right)||_(a.actionsWidth)||_(b.actionsWidth.right)||_(b.actionsWidth):_((a.actionsWidth||0).left)||_(a.actionsWidth)||_(b.actionsWidth.left)||_(b.actionsWidth))}function aA(c,e){if(c){var b=i.scrollTop(),d=c.is(".mbsc-lv-item")?c[0].offsetHeight:0,a=c.offset().top+(H?b-bw:0);e?(a<b||b+av<a+d)&&i.scrollTop(a):a<b?i.scrollTop(a):b+av<a+d&&i.scrollTop(Math.min(a,a+d-av/2))}}function bC(c,a,e,f,i){var j=a.parent(),g=a.prev();f=f||d,g[0]===z[0]&&(g=z.prev()),b.rtl&&(c="l"===c?"r":"l"),x[0]!==a[0]?(p("onNavStart",{level:al,direction:c,list:a[0]}),bn.prepend(a.addClass("mbsc-lv-v mbsc-lv-sl-new")),aA(h),cu(bn,"mbsc-lv-sl-"+c,function(){x.removeClass("mbsc-lv-sl-curr"),a.removeClass("mbsc-lv-sl-new").addClass("mbsc-lv-sl-curr"),aZ&&aZ.length?x.removeClass("mbsc-lv-v").insertAfter(aZ):aX.append(x.removeClass("mbsc-lv-v")),aZ=g,aX=j,x=a,aA(e,i),f.call(v,e),p("onNavEnd",{level:al,direction:c,list:a[0]})})):(aA(e,i),f.call(v,e))}function bD(a,b){S||(a.hasClass("mbsc-lv-parent")?(al++,bC("r",r[a.attr("data-ref")].child,null,b)):a.hasClass("mbsc-lv-back")&&(al--,bC("l",r[a.attr("data-back")].parent,r[a.attr("data-back")].item,b)))}function cu(a,e,c){var g;function f(){clearTimeout(g),S--,a.off(P,f).removeClass(e),c.call(v,a)}c=c||d,b.animation&&"mbsc-lv-item-none"!==e?(S++,a.on(P,f).addClass(e),g=setTimeout(f,250)):c.call(v,a)}function bF(a,b){var c,d=a.attr("data-ref");c=bE[d]=bE[d]||[],b&&c.push(b),a.attr("data-action")||(b=c.shift())&&(a.attr("data-action",1),b(function(){a.removeAttr("data-action"),c.length?bF(a):delete bE[d]}))}function bH(b,f,g){var e,d;b&&b.length&&(e=100/(b.length+2),a.each(b,function(h,a){void 0===a.key&&(a.key=bl++),void 0===a.percent&&(a.percent=f*e*(h+1),g&&((d=c({},a)).key=bl++,d.percent=-e*(h+1),b.push(d),aS[d.key]=d)),aS[a.key]=a}))}function bJ(a){ci(a)&&a.addClass(am).attr("data-selected","true").attr("aria-selected","true")}function aV(a){a.removeClass(am).removeAttr("data-selected").removeAttr("aria-selected")}C.call(this,cA,cD,!0),g.animate=function(a,b,c){cu(a,"mbsc-lv-item-"+b,c)},g.add=function(C,t,j,s,y,k){var x,m,o,z,i,l,q="",e=void 0===y?w:$(y),f=e,c="object"!==Z(t)?a("<"+ab+' data-ref="'+ao+++'" data-id="'+C+'">'+t+"</"+ab+">"):a(t),u=c[0],A=u.style,B=c.attr("data-pos")<0?"left":"right",n=c.attr("data-ref");s=s||d,n||(n=ao++,c.attr("data-ref",n)),bT(c),k||g.addUndoAction(function(a){z?g.navigate(e,function(){f.remove(),e.removeClass("mbsc-lv-parent").children(".mbsc-lv-arr").remove(),i.child=e.children(O),g.remove(c,null,a,!0)}):g.remove(c,null,a,!0)},!0),bF(c,function(d){ah(c.css("top","").removeClass("mbsc-lv-item-undo")),e.is(I)?(l=e.attr("data-ref"),e.children(O).length||(z=!0,e.append("<"+aE+"></"+aE+">"))):l=e.children(".mbsc-lv-back").attr("data-back"),(i=r[l])&&(i.child.length?f=i.child:(e.addClass("mbsc-lv-parent").prepend(bx),f=e.children(O).prepend(by).addClass("mbsc-lv"),i.child=f,a(".mbsc-lv-back",e).attr("data-back",l))),r[n]={item:c,child:c.children(O),parent:f,ref:l},o=az(f),m=o.length,null==j&&(j=m),k&&(q="mbsc-lv-item-new-"+(k?B:"")),ck(c.addClass(q)),!1!==j&&(m?j<m?c.insertBefore(o.eq(j)):c.insertAfter(o.eq(m-1)):(x=a(".mbsc-lv-back",f)).length?c.insertAfter(x):f.append(c)),h.trigger("mbsc-refresh"),b.animateAddRemove&&f.hasClass("mbsc-lv-v")?(A.height=u.offsetHeight+"px",g.animate(c,k&&aM===n?"none":"expand",function(a){g.animate(a,k?"add-"+B:"pop-in",function(a){A.height="",s.call(v,a.removeClass(q)),d()})})):(s.call(v,c.removeClass(q)),d()),p("onItemAdd",{target:u})})},g.swipe=function(a,d,b,e,g){var c;a=$(a),f=a,aC=e,L=af=!0,b=void 0===b?300:b,A=0<d?1:-1,cc(),bY(),at(a,d,b),clearTimeout(cd),clearInterval(bi),bi=setInterval(function(){c=j,j=bO(a)/an*100,bM(c)},10),cd=setTimeout(function(){clearInterval(bi),c=j,j=d,bM(c),bV(g),L=af=aC=!1},b)},g.openStage=function(b,a,c,d){aS[a]&&g.swipe(b,aS[a].percent,c,d)},g.openActions=function(a,b,d,e){a=$(a);var c=cq(aq[a.attr("data-type")||"defaults"],"left"==b?-1:1);g.swipe(a,"left"==b?-c:c,d,e)},g.close=function(a,b){g.swipe(a,0,b)},g.remove=function(o,i,e,n){var c,f,l,m,h,j,k;e=e||d,h=(c=$(o)).attr("data-ref"),c.length&&r[h]&&(f=c.parent(),m=az(f).index(c),k=c[0].style,function c(b){b&&(j=j||b.hasClass("mbsc-lv-v"),b.children("[data-ref]").each(function(){var b=a(this).attr("data-ref");r[b]&&(c(r[b].child),delete r[b])}))}(r[h].child),j&&(l=b.animation,b.animation=!1,g.navigate(c),b.animation=l),delete r[h],n||(c.attr("data-ref")===aM&&(aG=!0),g.addUndoAction(function(a){g.add(null,c,m,a,f,!0)},!0)),bF(c,function(a){i=i||(c.attr("data-pos")<0?"left":"right"),b.animateAddRemove&&f.hasClass("mbsc-lv-v")?g.animate(c.addClass("mbsc-lv-removed"),n?"pop-out":"remove-"+i,function(b){k.height=b[0].offsetHeight+"px",g.animate(b,"collapse",function(b){k.height="",ah(b.removeClass("mbsc-lv-removed")),!1!==e.call(v,b)&&b.remove(),a()})}):(!1!==e.call(v,c)&&c.remove(),a()),p("onItemRemove",{target:c[0]})}))},g.move=function(a,c,d,e,f,b){a=$(a),b||g.startActionTrack(),o.append(z),g.remove(a,d,null,b),g.add(null,a,c,e,f,b),b||g.endActionTrack()},g.navigate=function(a,d){var b,c;a=$(a),b=r[a.attr("data-ref")],c=function(c){for(var b=0,a=r[c.attr("data-ref")];a&&a.ref;)b++,a=r[a.ref];return b}(a),b&&(bC(al<=c?"r":"l",b.parent,a,d,!0),al=c)},g.showLoading=function(){aN=!0,aL.addClass("mbsc-show-lv-loading"),i.scrollTop(H?i[0].scrollHeight:a(b.context)[0].scrollHeight)},g.hideLoading=function(){aL.removeClass("mbsc-show-lv-loading"),setTimeout(function(){aN=!1},100)},g.select=function(b){bR||aV(a(I,h).filter("."+am)),bJ($(b))},g.deselect=function(a){aV($(a))},g._processSettings=function(){w.is("[mbsc-enhance]")&&(bB=!0,w.removeAttr("mbsc-enhance"))},g._init=function(){var f,k,d,q=w.find(O).length?"left":"right",c=0,m="",n="",p="";aE=b.listNode,O=b.listSelector,ab=b.itemNode,I=b.itemSelector,bR="multiple"==b.select,bt="off"!=b.select,"group"===(d=b.sort||b.sortable||!1)&&(d={group:!1,multiLevel:!0}),!0===d&&(d={group:!0,multiLevel:!0,handle:b.sortHandle}),d&&void 0===d.handle&&(d.handle=b.sortHandle),d.handle&&(bA=!0===d.handle?q:d.handle,cr='<div class="mbsc-lv-handle-c mbsc-lv-item-h-'+bA+' mbsc-lv-handle"><div class="'+b.handleClass+' mbsc-lv-handle-bar-c mbsc-lv-handle">'+b.handleMarkup+"</div></div>"),by="<"+ab+' class="mbsc-lv-item mbsc-lv-back mbsc-lv-item-actionable">'+b.backText+'<div class="mbsc-lv-arr mbsc-lv-ic mbsc-ic '+b.leftArrowClass+'"></div></'+ab+">",bx='<div class="mbsc-lv-arr mbsc-lv-ic mbsc-ic '+b.rightArrowClass+'"></div>',f="mbsc-no-touch mbsc-lv-cont mbsc-lv-"+b.theme+" mbsc-"+b.theme+(cG?" mbsc-lv-hb":"")+(b.rtl?" mbsc-lv-rtl mbsc-rtl":" mbsc-ltr")+(b.baseTheme?" mbsc-lv-"+b.baseTheme+" mbsc-"+b.baseTheme:"")+(b.animateIcons?" mbsc-lv-ic-anim":"")+(b.striped?" mbsc-lv-alt-row":"")+(b.fixedHeader?" mbsc-lv-has-fixed-header":"")+(d.handle?" mbsc-lv-handle-"+bA:""),g.sortable=d||!1,h?(h.attr("class",f),a(".mbsc-lv-handle-c",h).remove(),a(I,h).not(".mbsc-lv-back").removeClass("mbsc-lv-item"),i.off("orientationchange resize",aF),ag&&i.off("scroll touchmove",ag),i.off("scroll touchmove",bN)):(m+='<div class="mbsc-lv-multi-c"></div>',m+='<div class="mbsc-lv-ic-c"><div class="mbsc-lv-ic-s mbsc-lv-ic mbsc-ic mbsc-ic-none"></div><div class="mbsc-lv-ic-text"></div></div>',w.addClass("mbsc-lv mbsc-lv-v mbsc-lv-root").removeClass("mbsc-cloak").show(),o=a('<div class="mbsc-lv-stage-c">'+m+"</div>"),z=a(".mbsc-lv-ic-c",o),bg=a(".mbsc-lv-multi-c",o),ax=a(".mbsc-lv-ic-s",o),bb=a(".mbsc-lv-ic-text",o),K=a("<"+ab+' class="mbsc-lv-item mbsc-lv-ph"></'+ab+">"),aB=a('<div class="mbsc-lv-fill-item"></div>'),h=a('<div class="'+f+'"><'+aE+' class="mbsc-lv mbsc-lv-dummy"></'+aE+'><div class="mbsc-lv-sl-c"></div><div class="mbsc-lv-loading"><span class="mbsc-ic mbsc-ic-'+(b.loadingIcon||"loop2")+'"></span></div></div>'),cw=a(".mbsc-lv-dummy",h),aL=a(".mbsc-lv-loading",h),h.insertAfter(w),aF(),h.on("touchstart mousedown",".mbsc-lv-item",cB).on("touchmove",".mbsc-lv-item",bI).on("touchend touchcancel",".mbsc-lv-item",aU).on("click",".mbsc-lv-item",cC),v.addEventListener("click",bU,!0),h.on("touchstart mousedown",".mbsc-lv-ic-m",function(a){aC||(a.stopPropagation(),a.preventDefault()),bc=e(a,"X"),be=e(a,"Y")}).on("touchend mouseup",".mbsc-lv-ic-m",function(b){aC||("touchend"===b.type&&Q(),J&&!a(this).hasClass("mbsc-lv-ic-disabled")&&Math.abs(e(b,"X")-bc)<10&&Math.abs(e(b,"Y")-be)<10&&bL((j<0?l.rightMenu:l.leftMenu)[a(this).index()],b$,cz))}),bn=a(".mbsc-lv-sl-c",h).append(w.addClass("mbsc-lv-sl-curr")).attr("data-ref",ao++),x=w,aX=h),H="body"!==b.context,(i=a(H?b.context:window)).on("orientationchange resize",aF),i.on("scroll touchmove",bN),bl=0,(aq=b.itemGroups||{}).defaults={swipeleft:b.swipeleft,swiperight:b.swiperight,stages:b.stages,actions:b.actions,actionsWidth:b.actionsWidth,actionable:b.actionable},ck(w),a.each(aq,function(e,d){if(d.swipe=void 0!==d.swipe?d.swipe:b.swipe,d.actionable=void 0!==d.actionable?d.actionable:b.actionable,d.stages=d.stages||[],bH(d.stages,1,!0),bH(d.stages.left,1),bH(d.stages.right,-1),(d.stages.left||d.stages.right)&&(d.stages=[].concat(d.stages.left||[],d.stages.right||[])),aQ=!1,d.stages.length||(d.swipeleft&&d.stages.push({percent:-30,action:d.swipeleft}),d.swiperight&&d.stages.push({percent:30,action:d.swiperight})),a.each(d.stages,function(b,a){if(0===a.percent)return!(aQ=!0)}),aQ||d.stages.push({percent:0}),d.stages.sort(function(a,b){return a.percent-b.percent}),a.each(d.stages,function(a,b){if(0===b.percent)return d.start=a,!1}),aQ?d.left=d.right=d.stages[d.start]:(d.left=d.stages[d.start-1]||{},d.right=d.stages[d.start+1]||{}),d.actions){for(d.leftMenu=d.actions.left||d.actions,d.rightMenu=d.actions.right||d.leftMenu,p=n="",c=0;c<d.leftMenu.length;c++)n+="<div "+(d.leftMenu[c].color?'style="background-color: '+d.leftMenu[c].color+'"':"")+' class="mbsc-lv-ic-m mbsc-lv-ic mbsc-ic mbsc-ic-'+d.leftMenu[c].icon+'">'+(d.leftMenu[c].text||"")+"</div>";for(c=0;c<d.rightMenu.length;++c)p+="<div "+(d.rightMenu[c].color?'style="background-color: '+d.rightMenu[c].color+'"':"")+' class="mbsc-lv-ic-m mbsc-lv-ic mbsc-ic mbsc-ic-'+d.rightMenu[c].icon+'">'+(d.rightMenu[c].text||"")+"</div>";d.actions.left&&(d.swipe=d.actions.right?d.swipe:"right"),d.actions.right&&(d.swipe=d.actions.left?d.swipe:"left"),d.icons='<div class="mbsc-lv-multi mbsc-lv-multi-ic-left">'+n+'</div><div class="mbsc-lv-multi mbsc-lv-multi-ic-right">'+p+"</div>"}}),b.fixedHeader&&(k="mbsc-lv-fixed-header"+(H?" mbsc-lv-fixed-header-ctx mbsc-lv-"+b.theme+" mbsc-"+b.theme+(b.baseTheme?" mbsc-lv-"+b.baseTheme+" mbsc-"+b.baseTheme:""):""),R?(R.attr("class",k),bW()):R=a('<div class="'+k+'"></div>'),H?i.before(R):h.prepend(R),ag=bo(bW,200),i.on("scroll touchmove",ag)),b.hover&&(aP||h.on("mouseover.mbsc-lv",".mbsc-lv-item",function(){X&&X[0]==this||(bS(),X=a(this),aq[X.attr("data-type")||"defaults"].actions&&(cm=setTimeout(function(){aK?X=null:(bz=!0,g.openActions(X,cp,aP,!1))},cn)))}).on("mouseleave.mbsc-lv",bS),aP=b.hover.time||200,cn=b.hover.timeout||200,cp=b.hover.direction||b.hover||"right"),bB&&h.attr("mbsc-enhance",""),h.trigger("mbsc-enhance",[{theme:b.theme,lang:b.lang}])},g._destroy=function(){var b;aX.append(x),H&&R&&R.remove(),bB&&(w.attr("mbsc-enhance",""),(b=B[h[0].id])&&b.destroy()),v.removeEventListener("click",bU,!0),h.find(".mbsc-lv-txt,.mbsc-lv-img").removeClass("mbsc-lv-txt mbsc-lv-img"),h.find(O).removeClass("mbsc-lv mbsc-lv-v mbsc-lv-root mbsc-lv-sl-curr").find(I).removeClass("mbsc-lv-gr-title mbsc-lv-item mbsc-lv-item-enhanced mbsc-lv-parent mbsc-lv-img-left mbsc-lv-img-right mbsc-lv-item-ic-left mbsc-lv-item-ic-right").removeAttr("data-ref"),a(".mbsc-lv-back,.mbsc-lv-handle-c,.mbsc-lv-arr,.mbsc-lv-item-ic",h).remove(),w.insertAfter(h),h.remove(),o.remove(),i.off("orientationchange resize",aF),i.off("scroll touchmove",bN),ag&&i.off("scroll touchmove",ag)},ca=[],ak=[],bK=[],aW=0,g.startActionTrack=function(){aW||(bK=[]),aW++},g.endActionTrack=function(){--aW||ak.push(bK)},g.addUndoAction=function(c,d){var a={action:c,async:d};aW?bK.push(a):(ak.push([a]),ak.length>b.undoLimit&&ak.shift())},g.undo=function(){var a,b,c;function d(){b<0?(a$=!1,e()):(a=c[b],b--,a.async?a.action(d):(a.action(),d()))}function e(){(c=ca.shift())&&(a$=!0,b=c.length-1,d())}ak.length&&ca.push(ak.pop()),a$||e()},b=g.settings,p=g.trigger,g.init()}am="mbsc-selected",W="mbsc-lv-item-active",cG="ios"==m&&7<x,ao=1,bm="transparent",cC.prototype={_class:"listview",_hasDef:!0,_hasTheme:!0,_hasLang:!0,_defaults:{context:"body",actionsWidth:90,sortDelay:250,undoLimit:10,tap:Y,swipe:!0,quickSwipe:!0,animateAddRemove:!0,animateIcons:!0,animation:!0,revert:!0,vibrate:!0,actionable:!0,handleClass:"",handleMarkup:'<div class="mbsc-lv-handle-bar mbsc-lv-handle"></div><div class="mbsc-lv-handle-bar mbsc-lv-handle"></div><div class="mbsc-lv-handle-bar mbsc-lv-handle"></div>',listNode:"ul",listSelector:"ul,ol",itemNode:"li",itemSelector:"li",leftArrowClass:"mbsc-ic-arrow-left4",rightArrowClass:"mbsc-ic-arrow-right4",backText:"Back",undoText:"Undo",stages:[],select:"off"}},h("listview",l.ListView=cC,!(b.themes.listview.mobiscroll={leftArrowClass:"mbsc-ic-arrow-left5",rightArrowClass:"mbsc-ic-arrow-right5"})),cJ={batch:50,min:0,max:100,defaultUnit:"",units:null,unitNames:null,invalid:[],sign:!1,step:.05,scale:2,convert:function(a){return a},signText:"&nbsp;",wholeText:"Whole",fractionText:"Fraction",unitText:"Unit"},i.measurement=function(w){var S,g,A,q,i,z,k,l,h,m,F,M,J,d,U=c({},w.settings),b=c(w.settings,cJ,U),G={},B=[[]],r={},y={},E={},N=[],e=b.sign,o=b.units&&b.units.length,t=o?b.defaultUnit||b.units[0]:"",D=[],f=b.step<1,j=1<b.step?b.step:1,H=f?Math.max(b.scale,(b.step+"").split(".")[1].length):1,x=Math.pow(10,H),s=Math.round(f?b.step*x:b.step),P=0,L=0,n=0;function T(a){return Math.max(h,Math.min(m,f?a<0?Math.ceil(a):Math.floor(a):K(Math.round(a-P),s)+P))}function O(a){return f?K((Math.abs(a)-Math.abs(T(a)))*x-L,s)+L:0}function C(a){var b=T(a),c=O(a);return x<=c&&(a<0?b--:b++,c=0),[a<0?"-":"+",b,c]}function Q(a){var b=+a[i],c=f?a[q]/x*(b<0?-1:1):0;return(e&&"-"==a[0]?-1:1)*(b+c)}function K(b,a){return Math.round(b/a)*a}function v(a,c,d){return c!==d&&b.convert?b.convert.call(this,a,c,d):a}function I(d){var a,c;k=v(b.min,t,d),l=v(b.max,t,d),f?(h=k<0?Math.ceil(k):Math.floor(k),m=l<0?Math.ceil(l):Math.floor(l),F=O(k),M=O(l)):(h=Math.round(k),m=Math.round(l),m=h+Math.floor((m-h)/s)*s,P=h%s),a=h,c=m,e&&(c=Math.abs(a)>Math.abs(c)?Math.abs(a):Math.abs(c),a=a<0?0:a),y.min=a<0?Math.ceil(a/j):Math.floor(a/j),y.max=c<0?Math.ceil(c/j):Math.floor(c/j)}function R(a){return Q(a).toFixed(f?H:0)+(o?" "+D[a[z]]:"")}if(w.setVal=function(b,c,d,e,f){w._setVal(a.isArray(b)?R(b):b,c,d,e,f)},b.units)for(d=0;d<b.units.length;++d)J=b.units[d],D.push(b.unitNames&&b.unitNames[J]||J);if(e)if(e=!1,o)for(d=0;d<b.units.length;d++)v(b.min,t,b.units[d])<0&&(e=!0);else e=b.min<0;if(e&&(B[0].push({data:["-","+"],label:b.signText}),n++),y={label:b.wholeText,data:function(a){return h%j+a*j},getIndex:function(a){return Math.round((a-h%j)/j)}},B[0].push(y),i=n++,I(t),f){for(B[0].push(E),E.data=[],E.label=b.fractionText,d=L;d<x;d+=s)N.push(d),E.data.push({value:d,display:"."+u(d,H)});q=n++,S=Math.ceil(100/s),b.invalid&&b.invalid.length&&(a.each(b.invalid,function(c,b){var a=0<b?Math.floor(b):Math.ceil(b);0===a&&(a=b<=0?-.001:.001),r[a]=(r[a]||0)+1,0===b&&(r[a=.001]=(r[a]||0)+1)}),a.each(r,function(a,b){b<S?delete r[a]:r[a]=a}))}if(o){for(G={data:[],label:b.unitText,cssClass:"mbsc-msr-whl-unit",circular:!1},d=0;d<b.units.length;d++)G.data.push({value:d,display:D[d]});B[0].push(G)}return z=n,{wheels:B,minWidth:e&&f?70:80,showLabel:!1,formatValue:R,compClass:"mbsc-msr mbsc-sc",parseValue:function(r){var c,s=((("number"==typeof r?r+"":r)||b.defaultValue)+"").split(" "),j=+s[0],n=[],d="";return o&&(d=-1==(d=-1==(d=a.inArray(s[1],D))?a.inArray(t,b.units):d)?0:d),I(A=o?b.units[d]:""),(c=C(j=p(j=isNaN(j)?0:j,k,l)))[1]=p(c[1],h,m),g=j,e&&(n[0]=c[0],c[1]=Math.abs(c[1])),n[i]=c[1],f&&(n[q]=c[2]),o&&(n[z]=d),n},onCancel:function(){g=void 0},validate:function(J){var E,d,H,D,G,c=J.values,x=J.index,R=J.direction,L={},u=[],O={},B=o?b.units[c[z]]:"",P,S,T;if(e&&0===x&&(g=Math.abs(g)*("-"==c[0]?-1:1)),(x===i||x===q&&f||void 0===g||void 0===x)&&(g=Q(c),A=B),(o&&x===z&&A!==B||void 0===x)&&(I(B),g=v(g,A,B),A=B,d=C(g),void 0!==x&&(O[i]=y,w.changeWheel(O)),e&&(c[0]=d[0])),u[i]=[],e)for(u[0]=[],0<k&&(u[0].push("-"),c[0]="+"),l<0&&(u[0].push("+"),c[0]="-"),G=Math.abs("-"==c[0]?h:m),n=G+j;n<G+20*j;n+=j)u[i].push(n),L[n]=!0;return g=p(g,k,l),d=C(g),H=e?Math.abs(d[1]):d[1],E=e?"-"==c[0]:g<0,c[i]=H,E&&(d[0]="-"),f&&(c[q]=d[2]),a.each(f?r:b.invalid,function(b,a){if(e&&E){if(!(a<=0))return;a=Math.abs(a)}a=K(v(a,t,B),f?1:s),L[a]=!0,u[i].push(a)}),c[i]=w.getValidValue(i,H,R,L),d[1]=c[i]*(e&&E?-1:1),f&&(u[q]=[],P=e?c[0]+c[1]:(g<0?"-":"+")+Math.abs(d[1]),S=(k<0?"-":"+")+Math.abs(h),T=(l<0?"-":"+")+Math.abs(m),P===S&&a(N).each(function(b,a){(E?F<a:a<F)&&u[q].push(a)}),P===T&&a(N).each(function(b,a){(E?a<M:M<a)&&u[q].push(a)}),a.each(b.invalid,function(b,a){D=C(v(a,t,B)),(d[0]===D[0]||0===d[1]&&0===D[1]&&0===D[2])&&d[1]===D[1]&&u[q].push(D[2])})),{disabled:u,valid:c}}}},cK={min:0,max:100,defaultUnit:"km",units:["m","km","in","ft","yd","mi"]},bl={mm:.001,cm:.01,dm:.1,m:1,dam:10,hm:100,km:1e3,in:.0254,ft:.3048,yd:.9144,ch:20.1168,fur:201.168,mi:1609.344,lea:4828.032},i.distance=function(a){var b=c({},cK,a.settings);return c(a.settings,b,{sign:!1,convert:function(a,b,c){return a*bl[b]/bl[c]}}),i.measurement.call(this,a)},cM={min:0,max:100,defaultUnit:"N",units:["N","kp","lbf","pdl"]},bk={N:1,kp:9.80665,lbf:4.448222,pdl:.138255},i.force=function(a){var b=c({},cM,a.settings);return c(a.settings,b,{sign:!1,convert:function(a,b,c){return a*bk[b]/bk[c]}}),i.measurement.call(this,a)},cO={min:0,max:1e3,defaultUnit:"kg",units:["g","kg","oz","lb"],unitNames:{tlong:"t (long)",tshort:"t (short)"}},bh={mg:.001,cg:.01,dg:.1,g:1,dag:10,hg:100,kg:1e3,t:1e6,drc:1.7718452,oz:28.3495,lb:453.59237,st:6350.29318,qtr:12700.58636,cwt:50802.34544,tlong:1016046.9088,tshort:907184.74},i.mass=function(a){var b=c({},cO,a.settings);return c(a.settings,b,{sign:!1,convert:function(a,b,c){return a*bh[b]/bh[c]}}),i.measurement.call(this,a)},cQ={min:0,max:100,defaultUnit:"kph",units:["kph","mph","mps","fps","knot"],unitNames:{kph:"km/h",mph:"mi/h",mps:"m/s",fps:"ft/s",knot:"knot"}},bg={kph:1,mph:1.60934,mps:3.6,fps:1.09728,knot:1.852},i.speed=function(a){var b=c({},cQ,a.settings);return c(a.settings,b,{sign:!1,convert:function(a,b,c){return a*bg[b]/bg[c]}}),i.measurement.call(this,a)},cS={min:-20,max:40,defaultUnit:"c",units:["c","k","f","r"],unitNames:{c:"°C",k:"K",f:"°F",r:"°R"}},cT={c2k:function(a){return a+273.15},c2f:function(a){return 9*a/5+32},c2r:function(a){return 9*(a+273.15)/5},k2c:function(a){return a-273.15},k2f:function(a){return 9*a/5-459.67},k2r:function(a){return 9*a/5},f2c:function(a){return 5*(a-32)/9},f2k:function(a){return 5*(a+459.67)/9},f2r:function(a){return a+459.67},r2c:function(a){return 5*(a-491.67)/9},r2k:function(a){return 5*a/9},r2f:function(a){return a-459.67}},i.temperature=function(a){var b=c({},cS,a.settings);return c(a.settings,b,{sign:!0,convert:function(a,b,c){return cT[b+"2"+c](a)}}),i.measurement.call(this,a)},h("measurement",k),h("distance",k),h("force",k),h("mass",k),h("speed",k),h("temperature",k);function ay(q,H,F){var h,g,B,t,f,k,r,y,z,E,l,m,A,e,n,x,i,v={},u=1e3,b=this,j=a(q);function o(a){clearTimeout(E),E=setTimeout(function(){w(!a||"load"!==a.type)},200)}function G(){r&&p(a(this),!0)}function p(a,j){if(a.length){if(j=b._onItemTap(a,j),(h=a).parent()[0]==q){var k=a.offset().left,c=a[0].offsetLeft,d=a[0].offsetWidth,o=g.offset().left;l&&(c=n-c-d),"a"==e.variant?k<o?m.scroll(l?c+d-f:-c,u,!0):o+f<k+d&&m.scroll(l?c:f-c-d,u,!0):m.scroll((f/2-c-d/2)*(l?-1:1),u,!0)}j&&i("onItemTap",{target:a[0]})}}function D(){var c;b._initMarkup(g),j.find(".mbsc-ripple").remove(),b._$items=j.children(),b._$items.each(function(g){var f,d=a(this),e=d.attr("data-ref");e=e||cV++,0===g&&(c=d),h=h||b._getActiveItem(d),f="mbsc-scv-item mbsc-btn-e "+((b._getItemProps(d)||{}).cssClass||""),d.attr("data-ref",e).removeClass(v[e]).addClass(f),v[e]=f}),h=h||c,b._markupReady(g)}function w(h,i){var a=e.itemWidth,d=e.layout;if(b.contWidth=f=g.width(),b._checkResp())return!1;h&&z===f||!f||(z=f,s(d)&&(k=f?f/d:a)<a&&(d="liquid"),a&&("liquid"==d?k=f?f/Math.min(Math.floor(f/a),b._$items.length):a:"fixed"==d&&(k=a)),b._size(f,k),k&&j.children().css("width",k+"px"),b.totalWidth=n=q.offsetWidth,c(m.settings,{contSize:f,maxSnapScroll:!!e.paging&&1,maxScroll:0,minScroll:f<n?f-n:0,snap:e.paging?f:!!A&&(k||".mbsc-scv-item"),elastic:f<n&&(k||f)}),m.refresh(i))}C.call(this,q,H,!0),b.navigate=function(a,c){p(b._getItem(a),c)},b.next=function(b){if(h){var a=h.next();a.length&&p(h=a,b)}},b.prev=function(b){if(h){var a=h.prev();a.length&&p(h=a,b)}},b.refresh=b.position=function(a){D(),w(!1,a)},b._init=function(){var c;B=a(e.context),t=a("body"==e.context?window:e.context),b.__init(),l=e.rtl,A=!(!e.itemWidth||"fixed"==e.layout||void 0!==e.snap)||e.snap,c="mbsc-scv-c mbsc-no-touch mbsc-"+e.theme+" "+(e.cssClass||"")+" "+(e.wrapperClass||"")+(e.baseTheme?" mbsc-"+e.baseTheme:"")+(l?" mbsc-rtl":" mbsc-ltr")+(e.itemWidth?" mbsc-scv-hasw":"")+("body"==e.context?"":" mbsc-scv-ctx")+" "+(b._getContClass()||""),g?(g.attr("class",c),j.off(".mbsc-ripple")):((g=a('<div class="'+c+'"><div class="mbsc-scv-sc"></div></div>').on("click",".mbsc-scv-item",G).insertAfter(j)).find(".mbsc-scv-sc").append(j),g.find("img").on("load",o),t.on("orientationchange resize",o),y=dk(g[0],o,e.zone),m=new X(g[0],{axis:"X",contSize:0,maxScroll:0,maxSnapScroll:1,minScroll:0,snap:1,elastic:1,rtl:l,mousewheel:e.mousewheel,thresholdX:e.threshold,stopProp:e.stopProp,onStart:function(a){"touchstart"==a.domEvent.type&&(r=!1,x||(x=!0,B.find(".mbsc-no-touch").removeClass("mbsc-no-touch")))},onBtnTap:function(d){r=!0;var b=d.domEvent,c=b.target;"touchend"===b.type&&e.tap&&bQ(c,aY(a(c)),b)},onGestureStart:function(a){i("onGestureStart",a)},onGestureEnd:function(a){i("onGestureEnd",a)},onMove:function(a){i("onMove",a)},onAnimationStart:function(a){i("onAnimationStart",a)},onAnimationEnd:function(a){i("onAnimationEnd",a)}})),j.css("display","").addClass("mbsc-scv").removeClass("mbsc-cloak"),D(),i("onMarkupReady",{target:g[0]}),w()},b._size=d,b._initMarkup=d,b._markupReady=d,b._getContClass=d,b._getItemProps=d,b._getActiveItem=d,b.__init=d,b.__destroy=d,b._destroy=function(){b.__destroy(),t.off("orientationchange resize",o),j.removeClass("mbsc-scv").insertAfter(g).find(".mbsc-scv-item").each(function(){var b=a(this);b.width("").removeClass(v[b.attr("data-ref")])}),g.remove(),m.destroy(),y.detach()},b._getItem=function(c){return"object"!==Z(c)&&(c=b._$items.filter('[data-id="'+c+'"]')),a(c)},b._onItemTap=function(b,a){return void 0===a||a},e=b.settings,i=b.trigger,F||b.init()}cV=1,ay.prototype={_class:"scrollview",_hasDef:!0,_hasTheme:!0,_hasLang:!0,_responsive:!0,_defaults:{tap:Y,stopProp:!1,context:"body",layout:"liquid"}},l.ScrollView=ay;function aJ(l,p,o){var j,e,f,g,h,c,m=a(l),b=this;function n(){e&&"inline"!=e&&j.find(".mbsc-page").css("padding-"+e,"")}function k(a){a.addClass(h).attr("data-selected","true").attr("aria-selected","true")}function i(a){a.removeClass(h).removeAttr("data-selected").removeAttr("aria-selected")}ay.call(this,l,p,!0),b.select=function(a){f||i(b._$items.filter(".mbsc-ms-item-sel")),k(b._getItem(a))},b.deselect=function(a){i(b._getItem(a))},b.enable=function(a){b._getItem(a).removeClass("mbsc-disabled").removeAttr("data-disabled").removeAttr("aria-disabled")},b.disable=function(a){b._getItem(a).addClass("mbsc-disabled").attr("data-disabled","true").attr("aria-disabled","true")},b.setBadge=function(d,c){var e;d=b._getItem(d).attr("data-badge",c),(e=a(".mbsc-ms-badge",d)).length?c?e.html(c):e.remove():c&&d.append('<span class="mbsc-ms-badge">'+c+"</span>")},b._markupReady=function(a){b._hasIcons?a.addClass("mbsc-ms-icons"):a.removeClass("mbsc-ms-icons"),b._hasText?a.addClass("mbsc-ms-txt"):a.removeClass("mbsc-ms-txt"),b.__markupReady(a)},b._size=function(a,c){b.__size(a,c),"inline"!=e&&j.find(".mbsc-page").css("padding-"+e,l.offsetHeight+"px")},b._onItemTap=function(a,c){return!1!==b.__onItemTap(a,c)&&(void 0===c&&(c=!f),g&&c&&!a.hasClass("mbsc-disabled")&&(f?"true"==a.attr("data-selected")?i(a):k(a):(i(b._$items.filter(".mbsc-ms-item-sel")),k(a))),c)},b._getActiveItem=function(a){var b="true"==a.attr("data-selected");if(g&&!f&&b)return a},b._getItemProps=function(a){var e="true"==a.attr("data-selected"),f="true"==a.attr("data-disabled"),d=a.attr("data-icon"),g=a.attr("data-badge");return a.attr("data-role","button").attr("aria-selected",e?"true":"false").attr("aria-disabled",f?"true":"false").find(".mbsc-ms-badge").remove(),g&&a.append('<span class="mbsc-ms-badge">'+g+"</span>"),d&&(b._hasIcons=!0),a.text()&&(b._hasText=!0),{cssClass:"mbsc-ms-item "+(c.itemClass||"")+" "+(e?h:"")+(f?" mbsc-disabled "+(c.disabledClass||""):"")+(d?" mbsc-ms-ic mbsc-ic mbsc-ic-"+d:"")}},b._getContClass=function(){return" mbsc-ms-c mbsc-ms-"+c.variant+" mbsc-ms-"+e+(g?"":" mbsc-ms-nosel")+(b.__getContClass()||"")},b.__init=function(){b.___init(),j=a(c.context),n(),e=c.display,f="multiple"==c.select,g="off"!=c.select,h=" mbsc-ms-item-sel "+(c.activeClass||""),m.addClass("mbsc-ms mbsc-ms-base "+(c.groupClass||""))},b.__destroy=function(){m.removeClass("mbsc-ms mbsc-ms-base "+(c.groupClass||"")),n(),b.___destroy()},b.__onItemTap=d,b.__getContClass=d,b.__markupReady=d,b.__size=d,b.___init=d,b.___destroy=d,c=b.settings,o||b.init()}aJ.prototype={_defaults:c({},ay.prototype._defaults)};function bi(a,b){aJ.call(this,a,b,!0),this.___init=function(){},this.init()}bi.prototype={_class:"optionlist",_hasDef:!0,_hasTheme:!0,_hasLang:!0,_responsive:!0,_defaults:c({},aJ.prototype._defaults,{select:"multiple",variant:"a",display:"inline"})},l.Optionlist=bi,b.themes.optionlist=b.themes.navigation,h("optionlist",bi,!1);function cY(i,k){var d,g,h,b,f,e=a(i),j=e.is("ul,ol"),c=this;aJ.call(this,i,k,!0),c._initMarkup=function(){d&&d.remove(),g&&e.append(g.children())},c.__size=function(l,i){var j,m=i||72,n=c._$items.length,k=0;f.hide(),"bottom"==b.type&&(e.removeClass("mbsc-scv-liq"),d.remove(),c._$items.remove().each(function(c){var b=a(this);e.append(b),k+=i||this.offsetWidth||0,Math.round(k+(c<n-1?m:0))>l&&(j=!0,g.append(b.css("width","").addClass("mbsc-fr-btn-e")))}),d.attr("class",h+(b.moreIcon?" mbsc-menu-item-ic mbsc-ms-ic mbsc-ic mbsc-ic-"+b.moreIcon:"")).html(c._hasIcons&&c._hasText?b.moreText:""),j&&e.append(d)),"liquid"==b.layout&&e.addClass("mbsc-scv-liq")},c.__onItemTap=function(a){if(a.hasClass("mbsc-menu-item")&&!1!==c.trigger("onMenuShow",{target:a[0],menu:f}))return f.show(!1,!0),!1},c.__getContClass=function(){return"hamburger"==b.type?" mbsc-ms-hamburger":""},c.__markupReady=function(a){"hamburger"==b.type&&(g.append(c._$items.addClass("mbsc-fr-btn-e")),d.attr("class",h+(b.menuIcon?" mbsc-menu-item-ic mbsc-ms-ic mbsc-ic mbsc-ic-"+b.menuIcon:"")).html(b.menuText||""),e.append(d),b.menuText&&b.menuIcon||a.removeClass("mbsc-ms-icons"),b.menuText?a.addClass("mbsc-ms-txt"):a.removeClass("mbsc-ms-txt"))},c.___init=function(){var e;"tab"==b.type?(b.display=b.display||"top",b.variant=b.variant||"b"):"bottom"==b.type?(b.display=b.display||"bottom",b.variant=b.variant||"a"):"hamburger"==b.type&&(b.display=b.display||"inline",b.variant=b.variant||"a"),h="mbsc-scv-item mbsc-ms-item mbsc-btn-e mbsc-menu-item "+(b.itemClass||""),d||(d=a(j?"<li></li>":"<div></div>"),g=a(j?"<ul></ul>":"<div></div>").addClass("mbsc-scv mbsc-ms")),f=new w(g[0],{display:"bubble",theme:b.theme,lang:b.lang,context:b.context,buttons:[],anchor:d,onBeforeShow:function(b,a){e=null,a.settings.cssClass="mbsc-wdg mbsc-ms-a mbsc-ms-more"+(c._hasText?"":" mbsc-ms-more-icons")},onBeforeClose:function(){return c.trigger("onMenuHide",{target:e&&e[0],menu:f})},onMarkupReady:function(d,b){c.tap(b._markup.find(".mbsc-fr-c"),function(b){(e=a(b.target).closest(".mbsc-ms-item")).length&&!e.hasClass("mbsc-disabled")&&(c.navigate(e,!0),f.hide())})}})},c.___destroy=function(){f.destroy(),e.append(c._$items),d.remove()},b=c.settings,c.init()}cY.prototype={_class:"navigation",_hasDef:!0,_hasTheme:!0,_hasLang:!0,_responsive:!0,_defaults:c({},aJ.prototype._defaults,{type:"bottom",moreText:"More",moreIcon:"material-more-horiz",menuIcon:"material-menu"})},h("nav",l.Navigation=cY,!1),i.number=i.measurement,h("number",k);function bu(l,Q,S){var j,n,r,i,d,m,P,K,J,I,H,E,t,v,f,w,x,k,q,p=a(l),b=this,D=[],g=[],h={},G={},T={107:"+",109:"-"},B={48:0,49:1,50:2,51:3,52:4,53:5,54:6,55:7,56:8,57:9,96:0,97:1,98:2,99:3,100:4,101:5,102:6,103:7,104:8,105:9};function o(e){var c,g=d.validate.call(l,{values:f.slice(0),variables:h},b)||[],o=g&&g.disabled||[];if(b._isValid=!g.invalid,b._tempValue=d.formatValue.call(l,f.slice(0),h,b),i=f.length,w=g.length||k,b._isVisible){if(a(".mbsc-np-ph",j).each(function(b){a(this).html("ltr"==d.fill?i<=b?r:m||f[b]:k-w<=b?b+i<k?r:m||f[b+i-k]:"")}),a(".mbsc-np-cph",j).each(function(){a(this).html(h[a(this).attr("data-var")]||a(this).attr("data-ph"))}),i===k)for(c=0;c<=9;c++)o.push(c);for(a(".mbsc-np-btn",j).removeClass(n),c=0;c<o.length;c++)a('.mbsc-np-btn[data-val="'+o[c]+'"]',j).addClass(n);b._isValid?a(".mbsc-fr-btn-s .mbsc-fr-btn",j).removeClass(n):a(".mbsc-fr-btn-s .mbsc-fr-btn",j).addClass(n),b.live&&(b._hasValue=e||b._hasValue,u(e,!1,e),e&&x("onSet",{valueText:b._value}))}}function u(d,e,a,i){e&&o(),i||(q=f.slice(0),G=c({},h),D=g.slice(0),b._value=b._hasValue?b._tempValue:null),d&&(b._isInput&&p.val(b._hasValue&&b._isValid?b._value:""),x("onFill",{valueText:b._hasValue?b._tempValue:"",change:a}),a&&(b._preventChange=!0,p.trigger("change")))}function L(e){var a,b,c=e||[],d=[];for(g=[],h={},a=0;a<c.length;a++)/:/.test(c[a])?(b=c[a].split(":"),h[b[0]]=b[1],g.push(b[0])):(d.push(c[a]),g.push("digit"));return d}function M(a,b){!(i||b||d.allowLeadingZero)||a.hasClass("mbsc-disabled")||a.hasClass("mbsc-np-btn-empty")||i<k&&(g.push("digit"),f.push(b),o(!0))}function O(e){var b,a,c=e.attr("data-val"),k="false"!==e.attr("data-track"),j=e.attr("data-var");if(!e.hasClass("mbsc-disabled")){if(j&&(a=j.split(":"),k&&g.push(a[0]),h[a[0]]=void 0===a[2]?a[1]:h[a[0]]==a[1]?a[2]:a[1]),c.length+i<=w)for(b=0;b<c.length;++b)a=s(c[b])?+c[b]:c[b],(d.allowLeadingZero||i||a)&&(g.push("digit"),f.push(a),i=f.length);o(!0)}}function A(){var a,c,b=g.pop();if(i||"digit"!==b){if("digit"!==b&&h[b])for(delete h[b],c=g.slice(0),g=[],a=0;a<c.length;a++)c[a]!==b&&g.push(c[a]);else f.pop();o(!0)}}function F(){clearInterval(v),t=!1}function R(b){if(N(b,this)){if("keydown"==b.type&&32!=b.keyCode)return;!function(a){t=!0,P=e(a,"X"),K=e(a,"Y"),clearInterval(v),clearTimeout(v),A(),v=setInterval(function(){A()},150)}(b),"mousedown"==b.type&&a(document).on("mousemove",y).on("mouseup",C)}}function y(a){t&&(J=e(a,"X"),I=e(a,"Y"),H=J-P,E=I-K,(7<Math.abs(H)||7<Math.abs(E))&&F())}function C(b){t&&(b.preventDefault(),F(),"mouseup"==b.type&&a(document).off("mousemove",y).off("mouseup",C))}z.call(this,l,Q,!0),b.setVal=b._setVal=function(c,e,g,h){b._hasValue=null!=c,f=L(a.isArray(c)?c.slice(0):d.parseValue.call(l,c,b)),u(e,!0,void 0===g?e:g,h)},b.getVal=b._getVal=function(a){return b._hasValue||a?b[a?"_tempValue":"_value"]:null},b.setArrayVal=b.setVal,b.getArrayVal=function(a){return a?f.slice(0):b._hasValue?q.slice(0):null},b._readValue=function(){var a=p.val()||"";""!==a&&(b._hasValue=!0),m?(h={},g=[],f=[]):(h=b._hasValue?G:{},g=b._hasValue?D:[],f=b._hasValue&&q?q.slice(0):L(d.parseValue.call(l,a,b)),u(!1,!0))},b._fillValue=function(){u(b._hasValue=!0,!1,!0)},b._generateContent=function(){var e,f,b,c=1,a="";for(a+='<div class="mbsc-np-hdr"><div role="button" tabindex="0" aria-label="'+d.deleteText+'" class="mbsc-np-del mbsc-fr-btn-e mbsc-ic mbsc-ic-'+d.deleteIcon+'"></div><div class="mbsc-np-dsp">',a+=d.template.replace(/d/g,'<span class="mbsc-np-ph">'+r+"</span>").replace(/&#100;/g,"d").replace(/{([a-zA-Z0-9]*)\:?([a-zA-Z0-9\-\_]*)}/g,'<span class="mbsc-np-cph" data-var="$1" data-ph="$2">$2</span>'),a+="</div></div>",a+='<div class="mbsc-np-tbl-c mbsc-w-p"><div class="mbsc-np-tbl">',e=0;e<4;e++){for(a+='<div class="mbsc-np-row">',f=0;f<3;f++)10==(b=c)||12==c?b="":11==c&&(b=0),""===b?10==c&&d.leftKey?a+='<div role="button" tabindex="0" class="mbsc-np-btn mbsc-np-btn-custom mbsc-fr-btn-e" '+(d.leftKey.variable?'data-var="'+d.leftKey.variable+'"':"")+' data-val="'+(d.leftKey.value||"")+'" '+(void 0!==d.leftKey.track?' data-track="'+d.leftKey.track+'"':"")+">"+d.leftKey.text+"</div>":12==c&&d.rightKey?a+='<div role="button" tabindex="0" class="mbsc-np-btn mbsc-np-btn-custom mbsc-fr-btn-e" '+(d.rightKey.variable?'data-var="'+d.rightKey.variable+'"':"")+' data-val="'+(d.rightKey.value||"")+'" '+(void 0!==d.rightKey.track?' data-track="'+d.rightKey.track+'"':"")+" >"+d.rightKey.text+"</div>":a+='<div class="mbsc-np-btn mbsc-np-btn-empty"></div>':a+='<div tabindex="0" role="button" class="mbsc-np-btn mbsc-fr-btn-e" data-val="'+b+'">'+b+"</div>",c++;a+="</div>"}return a+="</div></div>"},b._markupReady=function(){j=b._markup,o()},b._attachEvents=function(c){c.on("keydown",function(b){var d;void 0!==T[b.keyCode]?(d=a('.mbsc-np-btn[data-var="sign:-:"]',c)).length&&(h.sign=107==b.keyCode?"-":"",O(d)):void 0!==B[b.keyCode]?M(a('.mbsc-np-btn[data-val="'+B[b.keyCode]+'"]',c),B[b.keyCode]):8==b.keyCode&&(b.preventDefault(),A())}),b.tap(a(".mbsc-np-btn",c),function(){var b=a(this);b.hasClass("mbsc-np-btn-custom")?O(b):M(b,+b.attr("data-val"))},!1,30,!0),a(".mbsc-np-del",c).on("touchstart mousedown keydown",R).on("touchmove mousemove",y).on("touchend mouseup keyup",C)},b.__init=function(){(d=b.settings).template=d.template.replace(/\\d/,"&#100;"),r=d.placeholder,k=(d.template.match(/d/g)||[]).length,n="mbsc-disabled "+(d.disabledClass||""),m=d.mask,x=b.trigger,m&&p.is("input")&&p.attr("type","password")},b._indexOf=function(b,c){var a;for(a=0;a<b.length;++a)if(b[a].toString()===c.toString())return a;return-1},S||b.init()}al={},bu.prototype={_hasDef:!0,_hasTheme:!0,_hasLang:!0,_responsive:!0,_class:"numpad",_presets:al,_defaults:c({},z.prototype._defaults,{template:"dd.dd",placeholder:"0",deleteIcon:"backspace",allowLeadingZero:!1,headerText:!1,fill:"rtl",compClass:"mbsc-np",deleteText:"Delete",decimalSeparator:".",thousandsSeparator:",",validate:d,parseValue:d,formatValue:function(g,k,l){var c,f=1,i=l.settings,j=i.placeholder,d=i.template,h=g.length,e=d.length,b="";for(c=0;c<e;c++)"d"==d[e-c-1]?(b=f<=h?g[h-f]+b:j+b,f++):b=d[e-c-1]+b;return a.each(k,function(a,c){b=b.replace("{"+a+"}",c)}),a("<div>"+b+"</div>").text()}})},l.Numpad=bu,b.themes.numpad=b.themes.frame,c$={min:0,max:99.99,scale:2,prefix:"",suffix:"",returnAffix:!1};function bL(c){for(var a=0,b=1,d=0;c.length;)3<a?b=3600:1<a&&(b=60),d+=c.pop()*b*(a%2?10:1),a++;return d}al.decimal=function(d){function g(d,f){for(var b,c=d.slice(0),a=0;c.length;)a=10*a+c.shift();for(b=0;b<e;b++)a/=10;return f?-1*a:a}function i(c){var a=g(c).toFixed(e).split(".");return a[0].replace(/\B(?=(\d{3})+(?!\d))/g,b.thousandsSeparator)+(a[1]?b.decimalSeparator+a[1]:"")}var l=c({},d.settings),b=c(d.settings,c$,l),e=b.scale,f=+b.min.toFixed(e),h=+b.max.toFixed(e),k=f<0,j=new RegExp(b.thousandsSeparator,"g");return d.setVal=function(a,b,c,e){return d._setVal(p(a,f,h),b,c,e)},d.getVal=function(e){var a=d._getVal(e),c=(a+"").replace(j,"").replace(b.decimalSeparator,".");return s(c)?+c:a},{template:(k?"{sign}":"")+b.prefix.replace(/d/g,"\\d")+Array((Math.floor(Math.max(h,Math.abs(f)))+"").length+1).join("d")+(e?"."+Array(e+1).join("d"):"")+b.suffix.replace(/d/g,"\\d"),leftKey:k?{text:"-/+",variable:"sign:-:",track:!1}:void 0,parseValue:function(g){var c,a,f=g||b.defaultValue,d=[];if(f&&(a=(f=(f+"").replace(j,"").replace(b.decimalSeparator,".")).match(/\d+\.?\d*/g)))for(a=(+a[0]).toFixed(e),c=0;c<a.length;c++)"."!=a[c]&&(+a[c]?d.push(+a[c]):d.length&&d.push(0));return g<0&&d.push("sign:-"),d},formatValue:function(a,c){var d=i(a);return(g(a,c&&"-"==c.sign)<0?"-":"")+(b.returnAffix?b.prefix+d+b.suffix:d)},validate:function(c){var e=c.values,l=i(e),j=g(e,c.variables&&"-"==c.variables.sign),k=[];return e.length||b.allowLeadingZero||k.push(0),d.isVisible()&&a(".mbsc-np-dsp",d._markup).html((c.variables.sign||"")+b.prefix+l+b.suffix),{disabled:k,invalid:h<j||j<f||!!b.invalid&&-1!=d._indexOf(b.invalid,j)}}}},bM=["h","m","s"],dc={min:0,max:362439,defaultValue:0,hourTextShort:"h",minuteTextShort:"m",secTextShort:"s"},al.timespan=function(d){var h=c({},d.settings),b=c(d.settings,dc,h),f={h:b.hourTextShort.replace(/d/g,"\\d"),m:b.minuteTextShort.replace(/d/g,"\\d"),s:b.secTextShort.replace(/d/g,"\\d")},e='d<span class="mbsc-np-sup mbsc-np-time">'+f.s+"</span>";function g(e){var c,b="",d=3600;return a(bM).each(function(g,a){c=Math.floor(e/d),e-=c*d,d/=60,(0<c||"s"==a&&!b)&&(b=b+(b?" ":"")+c+f[a])}),b}return 9<b.max&&(e="d"+e),99<b.max&&(e='<span class="mbsc-np-ts-m">'+(639<b.max?"d":"")+'d</span><span class="mbsc-np-sup mbsc-np-time">'+f.m+"</span>"+e),6039<b.max&&(e='<span class="mbsc-np-ts-h">'+(38439<b.max?"d":"")+'d</span><span class="mbsc-np-sup mbsc-np-time">'+f.h+"</span>"+e),d.setVal=function(a,b,c,e){return s(a)&&(a=g(a)),d._setVal(a,b,c,e)},d.getVal=function(a){return d._hasValue||a?bL(d.getArrayVal(a)):null},{template:e,parseValue:function(h){var d,e=h||g(b.defaultValue),c=[];return e&&a(bM).each(function(b,a){(d=new RegExp("(\\d+)"+f[a],"gi").exec(e))?9<(d=+d[1])?(c.push(Math.floor(d/10)),c.push(d%10)):(c.length&&c.push(0),(d||c.length)&&c.push(d)):c.length&&(c.push(0),c.push(0))}),c},formatValue:function(a){return g(bL(a))},validate:function(f){var c=f.values,a=bL(c.slice(0)),e=[];return c.length||e.push(0),{disabled:e,invalid:a>b.max||a<b.min||!!b.invalid&&-1!=d._indexOf(b.invalid,+a)}}}},dd={timeFormat:"hh:ii A",amText:"am",pmText:"pm"},al.time=function(l){var q=c({},l.settings),b=c(l.settings,dd,q),i=b.timeFormat.split(":"),d=b.timeFormat.match(/a/i),o=d?"a"==d[0]?b.amText:b.amText.toUpperCase():"",n=d?"a"==d[0]?b.pmText:b.pmText.toUpperCase():"",m=0,e=b.min?""+b.min.getHours():"",f=b.max?""+b.max.getHours():"",k=b.min?""+(b.min.getMinutes()<10?"0"+b.min.getMinutes():b.min.getMinutes()):"",g=b.max?""+(b.max.getMinutes()<10?"0"+b.max.getMinutes():b.max.getMinutes()):"",j=b.min?""+(b.min.getSeconds()<10?"0"+b.min.getSeconds():b.min.getSeconds()):"",h=b.max?""+(b.max.getSeconds()<10?"0"+b.max.getSeconds():b.max.getSeconds()):"";function p(c,e){var b,d="";for(b=0;b<c.length;++b)d+=c[b]+(b%2==(c.length%2==1?0:1)&&b!=c.length-1?":":"");return a.each(e,function(b,a){d+=" "+a}),d}return b.min&&b.min.setFullYear(2014,7,20),b.max&&b.max.setFullYear(2014,7,20),{placeholder:"-",allowLeadingZero:!0,template:(3==i.length?"dd:dd:dd":2==i.length?"dd:dd":"dd")+(d?'<span class="mbsc-np-sup">{ampm:--}</span>':""),leftKey:d?{text:o,variable:"ampm:"+o,value:"00"}:{text:":00",value:"00"},rightKey:d?{text:n,variable:"ampm:"+n,value:"00"}:{text:":30",value:"30"},parseValue:function(g){var a,c,e=g||b.defaultValue,f=[];if(e){if(c=(e+="").match(/\d/g))for(a=0;a<c.length;a++)f.push(+c[a]);d&&f.push("ampm:"+(e.match(new RegExp(b.pmText,"gi"))?n:o))}return f},formatValue:function(a,b){return p(a,b)},validate:function(n){var a=n.values,o=p(a,n.variables),c=3<=a.length?new Date(2014,7,20,""+a[0]+(a.length%2==0?a[1]:""),""+a[a.length%2==0?2:1]+a[a.length%2==0?3:2]):"";return{disabled:function(c){var a,t,s,r,q,w,o,n,u,v,l=[],p=2*i.length;if(m=p,c.length||(d&&(l.push(0),l.push(b.leftKey.value)),l.push(b.rightKey.value)),!d&&(p-c.length<2||1!=c[0]&&(2<c[0]||3<c[1])&&p-c.length<=2)&&(l.push("30"),l.push("00")),(d?1<c[0]||2<c[1]:1!=c[0]&&(2<c[0]||3<c[1]))&&c[0]&&(c.unshift(0),m=p-1),c.length==p)for(a=0;a<=9;++a)l.push(a);else if(1==c.length&&d&&1==c[0]||c.length&&c.length%2==0||!d&&2==c[0]&&3<c[1]&&c.length%2==1)for(a=6;a<=9;++a)l.push(a);if(u=void 0!==c[1]?""+c[0]+c[1]:"",v=+g==+(void 0!==c[3]?""+c[2]+c[3]:""),b.invalid)for(a=0;a<b.invalid.length;++a)if(w=b.invalid[a].getHours(),o=b.invalid[a].getMinutes(),n=b.invalid[a].getSeconds(),w==+u){if(2==i.length&&(o<10?0:+(""+o)[0])==+c[2]){l.push(o<10?o:+(""+o)[1]);break}if((n<10?0:+(""+n)[0])==+c[4]){l.push(n<10?n:+(""+n)[1]);break}}if(b.min||b.max){if(q=(s=+f==+u)&&v,r=(t=+e==+u)&&v,0===c.length){for(a=d?2:19<e?e[0]:3;a<=(1==e[0]?9:e[0]-1);++a)l.push(a);if(10<=e&&(l.push(0),2==e[0]))for(a=3;a<=9;++a)l.push(a);if(f&&f<10||e&&10<=e)for(a=f&&f<10?+f[0]+1:0;a<(e&&10<=e?e[0]:10);++a)l.push(a)}if(1==c.length){if(0===c[0])for(a=0;a<e[0];++a)l.push(a);if(e&&0!==c[0]&&(d?1==c[0]:2==c[0]))for(a=d?3:4;a<=9;++a)l.push(a);if(c[0]==e[0])for(a=0;a<e[1];++a)l.push(a);if(c[0]==f[0]&&!d)for(a=+f[1]+1;a<=9;++a)l.push(a)}if(2==c.length&&(t||s))for(a=s?+g[0]+1:0;a<(t?+k[0]:10);++a)l.push(a);if(3==c.length&&(s&&c[2]==g[0]||t&&c[2]==k[0]))for(a=s&&c[2]==g[0]?+g[1]+1:0;a<(t&&c[2]==k[0]?+k[1]:10);++a)l.push(a);if(4==c.length&&(r||q))for(a=q?+h[0]+1:0;a<(r?+j[0]:10);++a)l.push(a);if(5==c.length&&(r&&c[4]==j[0]||q&&c[4]==h[0]))for(a=q&&c[4]==h[0]?+h[1]+1:0;a<(r&&c[4]==j[0]?+j[1]:10);++a)l.push(a)}return l}(a),length:m,invalid:(d?!new RegExp("^(0?[1-9]|1[012])(:[0-5]\\d)?(:[0-5][0-9]) (?:"+b.amText+"|"+b.pmText+")$","i").test(o):!/^([0-1]?[0-9]|2[0-4]):([0-5][0-9])(:[0-5][0-9])?$/.test(o))||!!b.invalid&&-1!=l._indexOf(b.invalid,c)||!((!b.min||b.min<=c)&&(!b.max||c<=b.max))}}}},de={dateOrder:"mdy",dateFormat:"mm/dd/yy",delimiter:"/"},h("numpad",bu,!(al.date=function(k){var d,e,f,m,l=[],s=c({},k.settings),b=c(k.settings,az,de,s),g=b.dateOrder,q=b.min?""+(b.getMonth(b.min)+1):0,n=b.max?""+(b.getMonth(b.max)+1):0,p=b.min?""+b.getDay(b.min):0,o=b.max?""+b.getDay(b.max):0,h=b.min?""+b.getYear(b.min):0,i=b.max?""+b.getYear(b.max):0;for(g=(g=(g=g.replace(/y+/gi,"yyyy")).replace(/m+/gi,"mm")).replace(/d+/gi,"dd"),d=g.toUpperCase().indexOf("Y"),e=g.toUpperCase().indexOf("M"),f=g.toUpperCase().indexOf("D"),g="",l.push({val:d,n:"yyyy"},{val:e,n:"mm"},{val:f,n:"dd"}),l.sort(function(a,b){return a.val-b.val}),a.each(l,function(b,a){g+=a.n}),d=g.indexOf("y"),e=g.indexOf("m"),f=g.indexOf("d"),g="",m=0;m<8;++m)g+="d",m+1!=d&&m+1!=e&&m+1!=f||(g+=b.delimiter);function r(a){return new Date(+(""+a[d]+a[d+1]+a[d+2]+a[d+3]),""+a[e]+a[e+1]-1,+(""+a[f]+a[f+1]))}return k.getVal=function(a){return k._hasValue||a?r(k.getArrayVal(a)):null},{placeholder:"-",fill:"ltr",allowLeadingZero:!0,template:g,parseValue:function(f){var a,c=[],e=f||b.defaultValue,d=V(b.dateFormat,e,b);if(e)for(a=0;a<l.length;++a)c=/m/i.test(l[a].n)?c.concat(((b.getMonth(d)<9?"0":"")+(b.getMonth(d)+1)).split("")):/d/i.test(l[a].n)?c.concat(((b.getDay(d)<10?"0":"")+b.getDay(d)).split("")):c.concat((b.getYear(d)+"").split(""));return c},formatValue:function(a){return j(b.dateFormat,r(a),b)},validate:function(g){var c=g.values,a=r(c);return{disabled:function(c){var a,j,t,m,r,w,g=[],k=void 0!==c[d+3]?""+c[d]+c[d+1]+c[d+2]+c[d+3]:"",l=void 0!==c[e+1]?""+c[e]+c[e+1]:"",s=void 0!==c[f+1]?""+c[f]+c[f+1]:"",x=""+b.getMaxDayOfMonth(k||2012,l-1||0),u=h===k&&+q==+l,v=i===k&&+n==+l;if(b.invalid)for(a=0;a<b.invalid.length;++a){if(t=b.getYear(b.invalid[a]),m=b.getMonth(b.invalid[a]),r=b.getDay(b.invalid[a]),t==+k&&m+1==+l&&(r<10?0:+(""+r)[0])==+c[f]){g.push(r<10?r:+(""+r)[1]);break}if(m+1==+l&&r==+s&&(""+t).substring(0,3)==""+c[d]+c[d+1]+c[d+2]){g.push((""+t)[3]);break}if(t==+k&&r==+s&&(m<10?0:+(""+(m+1))[0])==+c[e]){g.push(m<10?m:+(""+(m+1))[1]);break}}if("31"!=s||c.length!=e&&c.length!=e+1||(1!=c[e]?g.push(2,4,6,9,11):g.push(1)),"30"==s&&0===c[e]&&c.length<=e+1&&g.push(2),c.length==e){for(a=i===k&&+n<10?1:2;a<=9;++a)g.push(a);h===k&&10<=+q&&g.push(0)}if(c.length==e+1){if(1==c[e]){for(a=i===k?+n[1]+1:3;a<=9;++a)g.push(a);if(h==k)for(a=0;a<+q[1];++a)g.push(a)}if(0===c[e]&&(g.push(0),i===k||h===k))for(a=i===k?+o<+s?+n:+n+1:0;a<=(h===k?q-1:9);++a)g.push(a)}if(c.length==f){for(a=v?1+(10<+o?+o[0]:0):+x[0]+1;a<=9;++a)g.push(a);if(u)for(a=0;a<(+p<10?0:p[0]);++a)g.push(a)}if(c.length==f+1){if(3<=c[f]||"02"==l)for(a=+x[1]+1;a<=9;++a)g.push(a);if(v&&+o[0]==c[f])for(a=+o[1]+1;a<=9;++a)g.push(a);if(u&&p[0]==c[f])for(a=0;a<+p[1];++a)g.push(a);if(0===c[f]&&(g.push(0),v||u))for(a=v?+o+1:1;a<=(u?p-1:9);++a)g.push(a)}if(void 0!==c[d+2]&&"02"==l&&"29"==s)for(j=+(""+c[d]+c[d+1]+c[d+2]+0);j<=+(""+c[d]+c[d+1]+c[d+2]+9);++j)g.push((w=j)%4==0&&w%100!=0||w%400==0?"":j%10);if(c.length==d){if(b.min)for(a=0;a<+h[0];++a)g.push(a);if(b.max)for(a=+i[0]+1;a<=9;++a)g.push(a);g.push(0)}if(b.min||b.max)for(j=1;j<4;++j)if(c.length==d+j){if(c[d+j-1]==+h[j-1]&&(3!=j||c[d+j-2]==+h[j-2]))for(a=0;a<+h[j]+(3==j&&c[e+1]&&+l<+q?1:0);++a)g.push(a);if(c[d+j-1]==+i[j-1]&&(3!=j||c[d+j-2]==+i[j-2]))for(a=+i[j]+(3==j&&+n<+l?0:1);a<=9;++a)g.push(a)}return g}(c),invalid:!("Invalid Date"!=a&&(!b.min||b.min<=a)&&(!b.max||a<=b.max))||!!b.invalid&&-1!=k._indexOf(b.invalid,a)}}}})),df={autoCorrect:!0,showSelector:!0,minRange:1,rangeTap:!0},i.range=function(d){function R(a,b){a&&(a.setFullYear(b.getFullYear()),a.setMonth(b.getMonth()),a.setDate(b.getDate()))}function r(e,b){var c=d._order,a=new Date(e);return void 0===c.h&&a.setHours(b?23:0),void 0===c.i&&a.setMinutes(b?59:0),void 0===c.s&&a.setSeconds(b?59:0),a.setMilliseconds(b?999:0),a}function A(a,b){return new Date(a.getFullYear(),a.getMonth(),a.getDate()+b)}function M(a){L?(e-f>b.maxRange-1&&(a?f=new Date(Math.max(v,e-b.maxRange+1)):e=new Date(Math.min(w,+f+b.maxRange-1))),e-f<b.minRange-1&&(a?f=new Date(Math.max(v,e-b.minRange+1)):e=new Date(Math.min(w,+f+b.minRange-1)))):(Math.ceil((e-f)/z)>F&&(a?f=r(Math.max(v,A(e,1-F)),!1):e=r(Math.min(w,A(f,F-1)),!0)),Math.ceil((e-f)/z)<H&&(a?f=r(Math.max(v,A(e,1-H)),!1):e=r(Math.min(w,A(f,H-1)),!0)))}function G(b,c){var a=!0;return b&&f&&e&&(M(h),M(!h)),f&&e||(a=!1),c&&P(),a}function I(){K&&k&&(a(".mbsc-range-btn",k).removeClass(B).removeAttr("aria-checked"),function(a){a.addClass(B).attr("aria-checked","true")}(a(".mbsc-range-btn",k).eq(h)))}function P(){var c,n,g,o,l,p=0,q=m||!h?" mbsc-cal-day-hl mbsc-cal-sel-start":" mbsc-cal-sel-start",r=m||h?" mbsc-cal-day-hl mbsc-cal-sel-end":" mbsc-cal-sel-end";if(d.startVal=f?j(i,f,b):"",d.endVal=e?j(i,e,b):"",k&&(a(".mbsc-range-btn-v-start",k).html(d.startVal||"&nbsp;"),a(".mbsc-range-btn-v-end",k).html(d.endVal||"&nbsp;"),c=f?new Date(f):null,g=e?new Date(e):null,!c&&g&&(c=new Date(g)),!g&&c&&(g=new Date(c)),l=h?g:c,a(".mbsc-cal-day-picker .mbsc-cal-day-hl",k).removeClass(C),a(".mbsc-cal-day-picker .mbsc-selected",k).removeClass("mbsc-cal-sel-start mbsc-cal-sel-end "+B).removeAttr("aria-selected"),c&&g))for(n=c.setHours(0,0,0,0),o=g.setHours(0,0,0,0);c<=g&&p<126;)a('.mbsc-cal-day[data-full="'+l.getFullYear()+"-"+(l.getMonth()+1)+"-"+l.getDate()+'"]',k).addClass(B+" "+(l.getTime()===n?q:"")+(l.getTime()===o?r:"")).attr("aria-selected","true"),l.setDate(l.getDate()+(h?-1:1)),l.setHours(0,0,0,0),p++}function D(a,b){return{h:a?a.getHours():b?23:0,i:a?a.getMinutes():b?59:0,s:a?a.getSeconds():b?59:0}}function Q(){f&&(p=!0,d.setDate(f,!1,0,!0),f=d.getDate(!0)),e&&(p=!0,d.setDate(e,!1,0,!0),e=d.getDate(!0))}var s,k,q,i,L,p,w,v,u,f,x,e,t,J,K,o=d._startDate,n=d._endDate,h=0,y=new Date,S=c({},d.settings),b=c(d.settings,df,S),E=b.anchor,m=b.rangeTap,z=864e5,H=Math.max(1,Math.ceil(b.minRange/z)),F=Math.max(1,Math.ceil(b.maxRange/z)),O="mbsc-disabled "+(b.disabledClass||""),B="mbsc-selected "+(b.selectedClass||""),C="mbsc-cal-day-hl",l=null===b.defaultValue?[]:b.defaultValue||[new Date(y.setHours(0,0,0,0)),new Date(y.getFullYear(),y.getMonth(),y.getDate()+6,23,59,59,999)];return m&&(b.tabs=!0),s=bj.call(this,d),i=d._format,L=/time/i.test(b.controls.join(",")),J="time"===b.controls.join(""),K=b.showSelector,w=b.max?r(g(b.max,i,b),!0):1/0,v=b.min?r(g(b.min,i,b),!1):-1/0,l[0]=g(l[0],i,b,b.isoParts),l[1]=g(l[1],i,b,b.isoParts),b.startInput&&d.attachShow(a(b.startInput),function(){h=0,b.anchor=E||a(b.startInput)}),b.endInput&&d.attachShow(a(b.endInput),function(){h=1,b.anchor=E||a(b.endInput)}),d._getDayProps=function(a,d){var b=f?new Date(f.getFullYear(),f.getMonth(),f.getDate()):null,c=e?new Date(e.getFullYear(),e.getMonth(),e.getDate()):null;return{selected:b&&c&&b<=a&&a<=e,cssClass:d.cssClass+" "+((m||!h)&&b&&b.getTime()===a.getTime()||(m||h)&&c&&c.getTime()===a.getTime()?C:"")+(b&&b.getTime()===a.getTime()?" mbsc-cal-sel-start":"")+(c&&c.getTime()===a.getTime()?" mbsc-cal-sel-end":"")}},d.setVal=function(q,l,m,c,p){var k,a=q||[];f=g(a[0],i,b,b.isoParts),e=g(a[1],i,b,b.isoParts),Q(),d.startVal=f?j(i,f,b):"",d.endVal=e?j(i,e,b):"",k=s.parseValue(h?e:f,d),c||(d._startDate=o=f,d._endDate=n=e),u=!0,d._setVal(k,l,m,c,p)},d.getVal=function(a){return a?[ad(f,b,i),ad(e,b,i)]:d._hasValue?[ad(o,b,i),ad(n,b,i)]:null},d.setActiveDate=function(c){var b;h="start"==c?0:1,b="start"==c?f:e,d.isVisible()&&(I(),m||(a(".mbsc-cal-table .mbsc-cal-day-hl",k).removeClass(C),b&&a('.mbsc-cal-day[data-full="'+b.getFullYear()+"-"+(b.getMonth()+1)+"-"+b.getDate()+'"]',k).addClass(C)),b&&(p=!0,d.setDate(b,!1,1e3,!0)))},d.getValue=d.getVal,c({},s,{highlight:!1,outerMonthChange:!1,formatValue:function(){return d.startVal+(b.endInput?"":d.endVal?" - "+d.endVal:"")},parseValue:function(c){var e=c?c.split(" - "):[],f=b.startInput?a(b.startInput).val():e[0],g=b.endInput?a(b.endInput).val():e[1];return b.defaultValue=l[1],n=g?V(i,g,b):l[1],b.defaultValue=l[0],o=f?V(i,f,b):l[2],b.defaultValue=l[h],d.startVal=o?j(i,o,b):"",d.endVal=n?j(i,n,b):"",d._startDate=o,d._endDate=n,s.parseValue(h?n:o,d)},onFill:function(c){!function(c){d._startDate=o=f,d._endDate=n=e,b.startInput&&(a(b.startInput).val(d.startVal),c&&a(b.startInput).trigger("change")),b.endInput&&(a(b.endInput).val(d.endVal),c&&a(b.endInput).trigger("change"))}(c.change)},onBeforeClose:function(a){if("set"===a.button&&!G(!0,!0))return d.setActiveDate(h?"start":"end"),!1},onHide:function(){s.onHide.call(d),h=0,k=null,b.anchor=E},onClear:function(){m&&(h=0)},onBeforeShow:function(){f=o||l[0],e=n||l[1],x=D(f,0),t=D(e,1),b.counter&&(b.headerText=function(){var a=f&&e?Math.max(1,Math.round((new Date(e).setHours(0,0,0,0)-new Date(f).setHours(0,0,0,0))/864e5)+1):0;return(1<a&&b.selectedPluralText||b.selectedText).replace(/{count}/,a)}),u=!0},onMarkupReady:function(g){var c;Q(),(h&&e||!h&&f)&&(p=!0,d.setDate(h?e:f,!1,0,!0)),P(),s.onMarkupReady.call(this,g),(k=a(g.target)).addClass("mbsc-range"),K&&(c='<div class="mbsc-range-btn-t" role="radiogroup"><div class="mbsc-range-btn-c mbsc-range-btn-start"><div role="radio" data-select="start" class="mbsc-fr-btn-e mbsc-fr-btn-nhl mbsc-range-btn">'+b.fromText+'<div class="mbsc-range-btn-v mbsc-range-btn-v-start">'+(d.startVal||"&nbsp;")+'</div></div></div><div class="mbsc-range-btn-c mbsc-range-btn-end"><div role="radio" data-select="end" class="mbsc-fr-btn-e mbsc-fr-btn-nhl mbsc-range-btn">'+b.toText+'<div class="mbsc-range-btn-v mbsc-range-btn-v-end">'+(d.endVal||"&nbsp;")+"</div></div></div></div>",b.headerText?a(".mbsc-fr-hdr",k).after(c):a(".mbsc-fr-w",k).prepend(c),I()),a(".mbsc-range-btn",k).on("touchstart click",function(b){N(b,this)&&(d._showDayPicker(),d.setActiveDate(a(this).attr("data-select")))})},onDayChange:function(a){a.active=h?"end":"start",q=!0},onSetDate:function(g){var c;p||(c=r(g.date,h),u&&!q||(m&&q&&(1==h&&c<f&&(h=0),h?c.setHours(t.h,t.i,t.s,999):c.setHours(x.h,x.i,x.s,0)),h?(e=new Date(c),t=D(e)):(f=new Date(c),x=D(f)),J&&b.autoCorrect&&(R(f,c),R(e,c)),m&&q&&!h&&(e=null))),J&&!b.autoCorrect&&e<f&&(e=new Date(e.setDate(e.getDate()+1))),d._isValid=G(u||q||b.autoCorrect,!p),g.active=h?"end":"start",!p&&m&&(q&&(h=h?0:1),I()),d.isVisible()&&(d._isValid?a(".mbsc-fr-btn-s .mbsc-fr-btn",d._markup).removeClass(O):a(".mbsc-fr-btn-s .mbsc-fr-btn",d._markup).addClass(O)),p=u=q=!1},onTabChange:function(a){"calendar"!=a.tab&&d.setDate(h?e:f,!1,1e3,!0),G(!0,!0)}})},h("range",k),h("scroller",k,!1),h("scrollview",ay,!1),dq={inputClass:"",rtl:!1,showInput:!0,groupLabel:"Groups",dataHtml:"html",dataText:"text",dataValue:"value",dataGroup:"group",dataDisabled:"disabled",filterPlaceholderText:"Type to filter",filterEmptyText:"No results",filterClearIcon:"material-close"},i.select=function(e,ad){var n,y,$,r,m,J,H,q,h,x,j,k,G,f,F,_="",Z={},U=1e3,B=this,g=a(B),ae=c({},e.settings),b=c(e.settings,dq,ae),C=a('<div class="mbsc-sel-empty">'+b.filterEmptyText+"</div>"),af=b.readonly,i={},P=b.layout||(/top|bottom|inline/.test(b.display)||b.filter?"liquid":""),R="liquid"==P||!b.touchUi,o=s(b.select)?b.select:"multiple"==b.select||g.prop("multiple"),l=o||!(!b.filter&&!b.tapSelect)&&1,D=this.id+"_dummy",Q=a('label[for="'+this.id+'"]').attr("for",D),ag=void 0!==b.label?b.label:Q.length?Q.text():g.attr("name"),v=b.group,A=!!b.data,u=A?!!b.group:a("optgroup",g).length,p=u&&v&&!1!==v.groupWheel,t=u&&v&&p&&!0===v.clustered,K=u&&(!v||!1!==v.header&&!t),z=g.val()||(o?[]:[""]),w=[];function O(h){var e,p,f,c,n,d,l=0,j=0,m={},o;(i={},q={},k=[],J=[],w.length=0,A)?a.each(y,function(g,a){n=a[b.dataText]+"",p=a[b.dataHtml],d=a[b.dataValue],f=a[b.dataGroup],c={value:d,html:p,text:n,index:g,cssClass:K?"mbsc-sel-gr-itm":""},i[d]=c,h&&!M(n,h)||(k.push(c),u&&(void 0===m[f]?(e={text:f,value:j,options:[],index:j},q[j]=e,m[f]=j,J.push(e),j++):e=q[m[f]],t&&(c.index=e.options.length),c.group=m[f],e.options.push(c)),a[b.dataDisabled]&&w.push(d))}):u?(o=!0,a("optgroup",g).each(function(b){q[b]={text:this.label,value:b,options:[],index:b},o=!0,a("option",this).each(function(a){c={value:this.value,text:this.text,index:t?a:l++,group:b,cssClass:K?"mbsc-sel-gr-itm":""},i[this.value]=c,h&&!M(this.text,h)||(o&&(J.push(q[b]),o=!1),k.push(c),q[b].options.push(c),this.disabled&&w.push(this.value))})})):a("option",g).each(function(a){c={value:this.value,text:this.text,index:a},i[this.value]=c,h&&!M(this.text,h)||(k.push(c),this.disabled&&w.push(this.value))}),_=b.defaultValue?b.defaultValue:k.length?k[0].value:"",K&&(k=[],l=0,a.each(q,function(e,b){b.options.length&&(d="__group"+e,c={text:b.text,value:d,group:e,index:l++,cssClass:"mbsc-sel-gr"},i[d]=c,k.push(c),w.push(c.value),a.each(b.options,function(b,a){a.index=l++,k.push(a)}))})),C&&(k.length?C.removeClass("mbsc-sel-empty-v"):C.addClass("mbsc-sel-empty-v"))}function W(b,d,f,c,g){var a,e=[];for(a=0;a<b.length;a++)e.push({value:b[a].value,display:b[a].html||b[a].text,cssClass:b[a].cssClass});return{circular:!1,multiple:d&&!c?1:c,cssClass:(d&&!c?"mbsc-sel-one":"")+" "+g,data:e,label:f}}function X(){return W(t&&q[m]?q[m].options:k,l,ag,o,"")}function Y(){var a=[[]];return p&&(H=W(J,l,b.groupLabel,!1,"mbsc-sel-gr-whl"),R?a[0][h]=H:a[h]=[H]),G=X(),R?a[0][f]=G:a[f]=[G],a}function E(b){o&&(b&&I(b)&&(b=b.split(",")),a.isArray(b)&&(b=b[0])),!i[j=null==b||""===b?_:b]&&k&&k.length&&(j=k[0].value),p&&(m=i[j]?i[j].group:null)}function N(a){return Z[a]||(i[a]?i[a].text:"")}function L(){var c,d="",a=e.getVal(),f=b.formatValue.call(B,e.getArrayVal(),e,!0);if(b.filter&&"inline"==b.display||n.val(f),g.is("select")&&A){if(o)for(c=0;c<a.length;c++)d+='<option value="'+a[c]+'">'+N(a[c])+"</option>";else d='<option value="'+(null===a?"":a)+'">'+f+"</option>";g.html(d)}B!==n[0]&&g.val(a)}function aa(){var a={};a[f]=X(),F=!0,e.changeWheel(a)}function M(b,a){return a=a.replace(/[-\/\\^$*+?.()|[\]{}]/g,"\\$&"),b.match(new RegExp(a,"ig"))}function ab(a){return b.data.dataField?a[b.data.dataField]:b.data.processResponse?b.data.processResponse(a):a}function T(c){var a={};O(c),E(j),b.wheels=Y(),a[f]=G,e._tempWheelArray[f]=j,p&&(a[h]=H,e._tempWheelArray[h]=m),e.changeWheel(a,0,!0),L()}function S(a){return e.trigger("onFilter",{filterText:a})}function V(a){a[h]!=m&&(m=a[h],j=q[m].options[0].value,a[f]=j,t?aa():e.setArrayVal(a,!1,!1,!0,U))}return e.setVal=function(a,g,j,b,k){if(l&&(null==a||o||(a=[a]),a&&I(a)&&(a=a.split(",")),e._tempSelected[f]=ac(a),b||(e._selected[f]=ac(a)),a=a?a[0]:null,p)){var c=i[a],d=c&&c.group;e._tempSelected[h]=ac([d]),b||(e._selected[h]=ac([d]))}e._setVal(a,g,j,b,k)},e.getVal=function(b,c){var a;return a=l?(a=bb(b?e._tempSelected[f]:e._selected[f]),o?a:a.length?a[0]:null):(a=b?e._tempWheelArray:e._hasValue?e._wheelArray:null)?a[f]:null,o?a:void 0!==a?u&&c?[i[a]?i[a].group:null,a]:a:null},e.refresh=function(c,f,e){e=e||d,c?(y=c,x||(b.data=c)):a.isArray(b.data)&&(y=b.data),!c&&x&&void 0===f?bT(b.data.url,function(a){y=ab(a),T(),e()},b.data.dataType):(T(f),e())},ad.invalid||(b.invalid=w),f=p?(h=0,1):(h=-1,0),l&&(o&&g.prop("multiple",!0),z&&I(z)&&(z=z.split(",")),e._selected[f]=ac(z)),e._$input&&e._$input.remove(),g.next().is(".mbsc-select-input")?n=g.next().removeAttr("tabindex"):b.input?n=a(b.input):(b.filter&&"inline"==b.display?e._$input=a('<div class="mbsc-sel-input-wrap"><input type="text" id="'+D+'" class="mbsc-select-input mbsc-control '+b.inputClass+'" readonly /></div>'):(n=a('<input type="text" id="'+D+'" class="mbsc-select-input mbsc-control '+b.inputClass+'" readonly />'),e._$input=n),b.showInput&&(e._$input.insertAfter(g),n=n||e._$input.find("#"+D))),e.attachShow(n.attr("placeholder",b.placeholder||"")),n[0]!==B&&(g.addClass("mbsc-sel-hdn").attr("tabindex",-1),b.showInput||g.attr("data-enhance",!1)),!l||b.rows%2||(b.rows=b.rows-1),b.filter&&($=b.filter.minLength||0),(x=b.data&&b.data.url)?e.refresh():(A&&(y=b.data),O(),E(g.val())),{layout:P,headerText:!1,anchor:n,compClass:"mbsc-sc mbsc-sel"+(l?" mbsc-sel-multi":""),setOnTap:!p||[!1,!0],formatValue:function(d,a,e){var b,c=[],g=e?a._selected:a._tempSelected;if(l){for(b in g[f])c.push(N(b));return c.join(", ")}return N(d[f])},tapSelect:l,parseValue:function(a){return E(void 0===a?g.val():a),p?[m,j]:[j]},validate:function(c){var d=c.index,a=[];return a[f]=b.invalid,t&&!F&&void 0===d&&aa(),F=!1,{disabled:a}},onRead:L,onFill:L,onMarkupReady:function(i,e){if(b.filter){var f,h,c,g=a(".mbsc-fr-w",i.target),d=a('<span class="mbsc-sel-filter-clear mbsc-ic mbsc-ic-'+b.filterClearIcon+'"></span>');"inline"==b.display?(f=n).parent().find(".mbsc-sel-filter-clear").remove():(g.find(".mbsc-fr-c").before('<div class="mbsc-input mbsc-sel-filter-cont mbsc-control-w mbsc-'+b.theme+(b.baseTheme?" mbsc-"+b.baseTheme:"")+'"><span class="mbsc-input-wrap"><input tabindex="0" type="text" class="mbsc-sel-filter-input mbsc-control"/></span></div>'),f=g.find(".mbsc-sel-filter-input")),r=null,c=f[0],f.prop("readonly",!1).attr("placeholder",b.filterPlaceholderText).parent().append(d),g.find(".mbsc-fr-c").prepend(C),e._activeElm=c,e.tap(d,function(){r=null,c.value="",e.refresh(),d.removeClass("mbsc-sel-filter-show-clear"),S("")}),f.on("keydown",function(a){13!=a.keyCode&&27!=a.keyCode||(a.stopPropagation(),c.blur())}).on("input",function(){clearTimeout(h),c.value.length?d.addClass("mbsc-sel-filter-show-clear"):d.removeClass("mbsc-sel-filter-show-clear"),h=setTimeout(function(){r!==c.value&&!1!==S(c.value)&&((r=c.value).length>=$||!r.length)&&(x&&b.data.remoteFilter?bT(b.data.url+encodeURIComponent(r),function(a){e.refresh(ab(a))},b.data.dataType):e.refresh(void 0,r))},500)})}},onBeforeShow:function(){o&&b.counter&&(b.headerText=function(){var c=0;return a.each(e._tempSelected[f],function(){c++}),(1<c&&b.selectedPluralText||b.selectedText).replace(/{count}/,c)}),E(g.val()),l&&p&&(e._selected[h]=ac([m])),b.filter&&O(void 0),e.settings.wheels=Y(),F=!0},onWheelGestureStart:function(a){a.index==h&&(b.readonly=[!1,!0])},onWheelAnimationEnd:function(c){var a=e.getArrayVal(!0);c.index==h?(b.readonly=af,l||V(a)):c.index==f&&a[f]!=j&&(j=a[f],p&&i[j]&&i[j].group!=m&&(m=i[j].group,a[h]=m,e._tempSelected[h]=ac([m]),e.setArrayVal(a,!1,!1,!0,U)))},onItemTap:function(a){var b;if(a.index==f&&(Z[a.value]=i[a.value].text,l&&!o&&a.selected))return!1;if(a.index==h&&l){if(a.selected)return!1;(b=e.getArrayVal(!0))[h]=a.value,V(b)}},onClose:function(){x&&b.data.remoteFilter&&r&&e.refresh()},onDestroy:function(){e._$input&&e._$input.remove(),g.removeClass("mbsc-sel-hdn").removeAttr("tabindex")}}},h("select",k),dh={autostart:!1,step:1,useShortLabels:!1,labels:["Years","Months","Days","Hours","Minutes","Seconds",""],labelsShort:["Yrs","Mths","Days","Hrs","Mins","Secs",""],startText:"Start",stopText:"Stop",resetText:"Reset",lapText:"Lap",hideText:"Hide",mode:"countdown"},i.timer=function(b){function J(a){return new Date(a.getUTCFullYear(),a.getUTCMonth(),a.getUTCDate(),a.getUTCHours(),a.getUTCMinutes(),a.getUTCSeconds(),a.getUTCMilliseconds())}function I(a,b,c){return(b||"")+(a<10?"0":"")+a+'<span class="mbsc-timer-lbl">'+c+"</span>"}function s(d){var b,c=[],e=function(m){var b={},g,e,n,f,o,c,d;if(E&&h[l].index>h.days.index){o=new Date,c=i?o:k,d=i?k:o;for(d=J(d),c=J(c),b.years=c.getFullYear()-d.getFullYear(),b.months=c.getMonth()-d.getMonth(),b.days=c.getDate()-d.getDate(),b.hours=c.getHours()-d.getHours(),b.minutes=c.getMinutes()-d.getMinutes(),b.seconds=c.getSeconds()-d.getSeconds(),b.fract=(c.getMilliseconds()-d.getMilliseconds())/10,g=j.length;0<g;g--)e=j[g-1],n=h[e],f=j[a.inArray(e,j)-1],h[f]&&b[e]<0&&(b[f]--,b[e]+="months"==f?32-new Date(c.getFullYear(),c.getMonth(),32).getDate():n.until+1);"months"==l&&(b.months+=12*b.years,delete b.years)}else a(j).each(function(c,a){h[a].index<=h[l].index&&(b[a]=Math.floor(m/h[a].limit),m-=b[a]*h[a].limit)});return b}(d);return a(j).each(function(d,a){r[a]&&(b=Math.max(Math.round(z/h[a].limit),1),c.push(Math.round(e[a]/b)*b))}),c}function D(a){E?(f=k-new Date,i=f<0&&(f*=-1,!0),w=!(e=0)):(void 0!==k?(w=!1,f=1e3*k,i="countdown"!=d.mode):(f=0,i="countdown"!=d.mode,w=i),a&&(e=0))}function A(){m?(a(".mbsc-fr-w",g).addClass("mbsc-timer-running mbsc-timer-locked"),a(".mbsc-timer-btn-toggle-c > div",g).text(d.stopText),b.buttons.start.icon&&a(".mbsc-timer-btn-toggle-c > div",g).removeClass("mbsc-ic-"+b.buttons.start.icon),b.buttons.stop.icon&&a(".mbsc-timer-btn-toggle-c > div",g).addClass("mbsc-ic-"+b.buttons.stop.icon),"stopwatch"==d.mode&&(a(".mbsc-timer-btn-resetlap-c > div",g).text(d.lapText),b.buttons.reset.icon&&a(".mbsc-timer-btn-resetlap-c > div",g).removeClass("mbsc-ic-"+b.buttons.reset.icon),b.buttons.lap.icon&&a(".mbsc-timer-btn-resetlap-c > div",g).addClass("mbsc-ic-"+b.buttons.lap.icon))):(a(".mbsc-fr-w",g).removeClass("mbsc-timer-running"),a(".mbsc-timer-btn-toggle-c > div",g).text(d.startText),b.buttons.start.icon&&a(".mbsc-timer-btn-toggle-c > div",g).addClass("mbsc-ic-"+b.buttons.start.icon),b.buttons.stop.icon&&a(".mbsc-timer-btn-toggle-c > div",g).removeClass("mbsc-ic-"+b.buttons.stop.icon),"stopwatch"==d.mode&&(a(".mbsc-timer-btn-resetlap-c > div",g).text(d.resetText),b.buttons.reset.icon&&a(".mbsc-timer-btn-resetlap-c > div",g).addClass("mbsc-ic-"+b.buttons.reset.icon),b.buttons.lap.icon&&a(".mbsc-timer-btn-resetlap-c > div",g).removeClass("mbsc-ic-"+b.buttons.lap.icon)))}var u,y,q,p,t,v,f,e,i,g,H,K=c({},b.settings),d=c(b.settings,dh,K),n=d.useShortLabels?d.labelsShort:d.labels,G=["resetlap","toggle"],j=["years","months","days","hours","minutes","seconds","fract"],h={years:{index:6,until:10,limit:31536e6,label:n[0],wheel:{}},months:{index:5,until:11,limit:2592e6,label:n[1],wheel:{}},days:{index:4,until:31,limit:864e5,label:n[2],wheel:{}},hours:{index:3,until:23,limit:36e5,label:n[3],wheel:{}},minutes:{index:2,until:59,limit:6e4,label:n[4],wheel:{}},seconds:{index:1,until:59,limit:1e3,label:n[5],wheel:{}},fract:{index:0,until:99,limit:10,label:n[6],prefix:".",wheel:{}}},r={},B=[],C=0,m=!1,o=!0,w=!1,z=Math.max(10,1e3*d.step),l=d.maxWheel,x="stopwatch"==d.mode||E,k=d.targetTime,E=k&&void 0!==k.getTime,F=[[]];return b.start=function(){if(o&&b.reset(),!m){if(D(),!w&&f<=e)return;o=!(m=!0),t=new Date,p=e,d.readonly=!0,b.setVal(s(i?e:f-e),!0,!0,!1,100),y=setInterval(function(){e=new Date-t+p,b.setVal(s(i?e:f-e),!0,!0,!1,Math.min(100,q-10)),!w&&f<=e+q&&(clearInterval(y),setTimeout(function(){b.stop(),e=f,b.setVal(s(i?e:0),!0,!0,!1,100),b.trigger("onFinish",{time:f}),o=!0},f-e))},q),A(),b.trigger("onStart")}},b.stop=function(){m&&(m=!1,clearInterval(y),e=new Date-t+p,A(),b.trigger("onStop",{ellapsed:e}))},b.toggle=function(){m?b.stop():b.start()},b.reset=function(){b.stop(),B=[],C=e=0,b.setVal(s(i?0:f),!0,!0,!1,1e3),b.settings.readonly=x,o=!0,x||a(".mbsc-fr-w",g).removeClass("mbsc-timer-locked"),b.trigger("onReset")},b.lap=function(){m&&(v=new Date-t+p,H=v-C,C=v,B.push(v),b.trigger("onLap",{ellapsed:v,lap:H,laps:B}))},b.resetlap=function(){m&&"stopwatch"==d.mode?b.lap():b.reset()},b.getTime=function(){return f},b.setTime=function(a){k=a/1e3,f=a},b.getEllapsedTime=function(){return o?0:m?new Date-t+p:e},b.setEllapsedTime=function(a,c){o||(p=e=a,t=new Date,b.setVal(s(i?e:f-e),!0,c,!1,1e3))},D(!0),l||f||(l="minutes"),"inline"!==d.display&&G.unshift("hide"),l||a(j).each(function(b,a){if(!l&&f>=h[a].limit)return l=a,!1}),a(j).each(function(c,b){!function(d){var e=1,b=h[d],c=b.wheel,f=b.prefix,i=b.until,g=h[j[a.inArray(d,j)-1]];if(b.index<=h[l].index&&(!g||g.limit>z))if(r[d]||F[0].push(c),r[d]=1,c.data=[],c.label=b.label||"",c.cssClass="mbsc-timer-whl-"+d,z>=b.limit&&(e=Math.max(Math.round(z/b.limit),1),q=e*b.limit),d==l)c.min=0,c.data=function(a){return{value:a,display:I(a,f,b.label)}},c.getIndex=function(a){return a};else for(u=0;u<=i;u+=e)c.data.push({value:u,display:I(u,f,b.label)})}(b)}),q=Math.max(97,q),d.autostart&&setTimeout(function(){b.start()},0),b.handlers.toggle=b.toggle,b.handlers.start=b.start,b.handlers.stop=b.stop,b.handlers.resetlap=b.resetlap,b.handlers.reset=b.reset,b.handlers.lap=b.lap,b.buttons.toggle={parentClass:"mbsc-timer-btn-toggle-c",text:d.startText,icon:d.startIcon,handler:"toggle"},b.buttons.start={text:d.startText,icon:d.startIcon,handler:"start"},b.buttons.stop={text:d.stopText,icon:d.stopIcon,handler:"stop"},b.buttons.reset={text:d.resetText,icon:d.resetIcon,handler:"reset"},b.buttons.lap={text:d.lapText,icon:d.lapIcon,handler:"lap"},b.buttons.resetlap={parentClass:"mbsc-timer-btn-resetlap-c",text:d.resetText,icon:d.resetIcon,handler:"resetlap"},b.buttons.hide={parentClass:"mbsc-timer-btn-hide-c",text:d.hideText,icon:d.closeIcon,handler:"cancel"},{minWidth:100,wheels:F,headerText:!1,readonly:x,buttons:G,compClass:"mbsc-timer mbsc-sc",parseValue:function(){return s(i?0:f)},formatValue:function(c){var d="",b=0;return a(j).each(function(e,a){"fract"!=a&&r[a]&&(d+=c[b]+("seconds"==a&&r.fract?"."+c[b+1]:"")+" "+n[e]+" ",b++)}),d},validate:function(b){var d=b.values,e=b.index,c=0;o&&void 0!==e&&(k=0,a(j).each(function(b,a){r[a]&&(k+=h[a].limit*d[c],c++)}),k/=1e3,D(!0))},onBeforeShow:function(){d.showLabel=!0},onMarkupReady:function(b){g=a(b.target),A(),x&&a(".mbsc-fr-w",g).addClass("mbsc-timer-locked")},onPosition:function(b){a(".mbsc-fr-w",b.target).css("min-width",0).css("min-width",a(".mbsc-fr-btn-cont",b.target)[0].offsetWidth)},onDestroy:function(){clearInterval(y)}}},h("timer",k),di={wheelOrder:"hhiiss",useShortLabels:!1,min:0,max:1/0,labels:["Years","Months","Days","Hours","Minutes","Seconds"],labelsShort:["Yrs","Mths","Days","Hrs","Mins","Secs"]};function f(a){return a<-1e-7?Math.ceil(a-1e-7):Math.floor(a+1e-7)}function bf(b,a,d){b=parseInt(b),a=parseInt(a),d=parseInt(d);var i,c,e,h,g=new Array(0,0,0);return i=1582<b||1582==b&&10<a||1582==b&&10==a&&14<d?f(1461*(b+4800+f((a-14)/12))/4)+f(367*(a-2-12*f((a-14)/12))/12)-f(3*f((b+4900+f((a-14)/12))/100)/4)+d-32075:367*b-f(7*(b+5001+f((a-9)/7))/4)+f(275*a/9)+d+1729777,h=f(((c=i-1948440+10632)-1)/10631),e=f((10985-(c=c-10631*h+354))/5316)*f(50*c/17719)+f(c/5670)*f(43*c/15238),c=c-f((30-e)/15)*f(17719*e/50)-f(e/16)*f(15238*e/43)+29,a=f(24*c/709),d=c-f(709*a/24),b=30*h+e-30,g[2]=d,g[1]=a,g[0]=b,g}i.timespan=function(f){function l(c){var b={};return a(n).each(function(e,a){b[a]=h[a]?Math.floor(c/d[a].limit):0,c-=b[a]*d[a].limit}),b}function o(a,b,c){return(a<10&&b?"0":"")+a+'<span class="mbsc-ts-lbl">'+c+"</span>"}function r(b){var c=0;return a.each(e,function(a,e){isNaN(+b[0])||(c+=d[e.v].limit*b[a])}),c}var i,u,p,m,j,w=c({},f.settings),b=c(f.settings,di,w),q=b.wheelOrder,g=b.useShortLabels?b.labelsShort:b.labels,n=["years","months","days","hours","minutes","seconds"],d={years:{ord:0,index:6,until:10,limit:31536e6,label:g[0],re:"y",wheel:{}},months:{ord:1,index:5,until:11,limit:2592e6,label:g[1],re:"m",wheel:{}},days:{ord:2,index:4,until:31,limit:864e5,label:g[2],re:"d",wheel:{}},hours:{ord:3,index:3,until:23,limit:36e5,label:g[3],re:"h",wheel:{}},minutes:{ord:4,index:2,until:59,limit:6e4,label:g[4],re:"i",wheel:{}},seconds:{ord:5,index:1,until:59,limit:1e3,label:g[5],re:"s",wheel:{}}},e=[],t=b.steps||[],h={},k="seconds",x=b.defaultValue||Math.max(b.min,Math.min(0,b.max)),v=[[]];return a(n).each(function(b,a){-1<(u=q.search(new RegExp(d[a].re,"i")))&&(e.push({o:u,v:a}),d[a].index>d[k].index&&(k=a))}),e.sort(function(a,b){return a.o>b.o?1:-1}),a.each(e,function(b,a){h[a.v]=b+1,v[0].push(d[a.v].wheel)}),m=l(b.min),j=l(b.max),a.each(e,function(b,a){!function(c){var f=!1,e=t[h[c]-1]||1,b=d[c],g=b.label,a=b.wheel;if(a.data=[],a.label=b.label,q.match(new RegExp(b.re+b.re,"i"))&&(f=!0),c==k)a.min=m[c],a.max=j[c],a.data=function(a){return{value:a*e,display:o(a*e,f,g)}},a.getIndex=function(a){return Math.round(a/e)};else for(i=0;i<=b.until;i+=e)a.data.push({value:i,display:o(i,f,g)})}(a.v)}),f.getVal=function(a,b){return b?f._getVal(a):f._hasValue||a?r(f.getArrayVal(a)):null},{minWidth:100,showLabel:!0,wheels:v,compClass:"mbsc-ts mbsc-sc",parseValue:function(f){var g,c=[];return s(f)||!f?(p=l(f||x),a.each(e,function(b,a){c.push(p[a.v])})):a.each(e,function(e,a){g=new RegExp("(\\d+)\\s?("+b.labels[d[a.v].ord]+"|"+b.labelsShort[d[a.v].ord]+")","gi").exec(f),c.push(g?g[1]:0)}),a(c).each(function(a,b){c[a]=function(b,a){return Math.floor(b/a)*a}(b,t[a]||1)}),c},formatValue:function(c){var b="";return a.each(e,function(a,e){b+=+c[a]?c[a]+" "+d[e.v].label+" ":""}),b?b.replace(/\s+$/g,""):0},validate:function(s){var g,b,c,e,i=s.values,t=s.direction,o=[],p=!0,q=!0;return a(n).each(function(s,n){if(void 0!==h[n]){if(c=h[n]-1,o[c]=[],e={},n!=k){if(p)for(b=j[n]+1;b<=d[n].until;b++)e[b]=!0;if(q)for(b=0;b<m[n];b++)e[b]=!0}i[c]=f.getValidValue(c,i[c],t,e),g=l(r(i)),p=p&&g[n]==j[n],q=q&&g[n]==m[n],a.each(e,function(a){o[c].push(a)})}}),{disabled:o}}}},h("timespan",k),i.treelist=cA,h("treelist",k),h("popup",w,!1),h("widget",cf,!1),aV.hijri={getYear:function(a){return bf(a.getFullYear(),a.getMonth()+1,a.getDate())[0]},getMonth:function(a){return--bf(a.getFullYear(),a.getMonth()+1,a.getDate())[1]},getDay:function(a){return bf(a.getFullYear(),a.getMonth()+1,a.getDate())[2]},getDate:function(b,a,d,e,g,h,i){a<0&&(b+=Math.floor(a/12),a=12+a%12),11<a&&(b+=Math.floor(a/12),a%=12);var c=function(e,c,g){e=parseInt(e),c=parseInt(c),g=parseInt(g);var k,a,b,h,d,j,i=new Array(3);return e=2299160<(k=f((11*e+3)/30)+354*e+30*c-f((c-1)/2)+g+1948440-385)?(h=f(4*(a=68569+k)/146097),a-=f((146097*h+3)/4),d=f(4e3*(a+1)/1461001),a=a-f(1461*d/4)+31,b=f(80*a/2447),g=a-f(2447*b/80),c=b+2-12*(a=f(b/11)),100*(h-49)+d+a):(j=f(((b=1402+k)-1)/1461),h=f(((a=b-1461*j)-1)/365)-f(a/1461),b=f(80*(d=a-365*h+30)/2447),g=d-f(2447*b/80),c=b+2-12*(d=f(b/11)),4*j+h+d-4716),i[2]=g,i[1]=c,i[0]=e,i}(b,+a+1,d);return new Date(c[0],c[1]-1,c[2],e||0,g||0,h||0,i||0)},getMaxDayOfMonth:function(b,a){return[30,29,30,29,30,29,30,29,30,29,30,29][a]+(11===a&&(11*b+14)%30<11?1:0)}},b.i18n.ar={rtl:!0,setText:"تعيين",cancelText:"إلغاء",clearText:"مسح",selectedText:"{count} المحدد",dateFormat:"dd/mm/yy",dayNames:["الأحد","الاثنين","الثلاثاء","الأربعاء","الخميس","الجمعة","السبت"],dayNamesShort:["أحد","اثنين","ثلاثاء","أربعاء","خميس","جمعة","سبت"],dayNamesMin:["ح","ن","ث","ر","خ","ج","س"],dayText:"يوم",hourText:"ساعات",minuteText:"الدقائق",monthNames:["كانون الثاني","شهر فبراير","مارس","أبريل","قد","يونيو","يوليو","أغسطس","سبتمبر","شهر اكتوبر","شهر نوفمبر","ديسمبر"],monthNamesShort:["كانون الثاني","شهر فبراير","مارس","أبريل","قد","يونيو","يوليو","أغسطس","سبتمبر","شهر اكتوبر","شهر نوفمبر","ديسمبر"],monthText:"شهر",secText:"ثواني",amText:"ص",pmText:"م",timeFormat:"hh:ii A",yearText:"عام",nowText:"الآن",firstDay:0,dateText:"تاريخ",timeText:"وقت",closeText:"إغلاق",todayText:"اليوم",prevMonthText:"الشهر السابق",nextMonthText:"الشهر القادم",prevYearText:"السنه السابقة",nextYearText:"العام القادم",allDayText:"اليوم كله",noEventsText:"لا توجد احداث",eventText:"الحدث",eventsText:"أحداث",moreEventsText:"واحد آخر",moreEventsPluralText:"اثنان آخران {count}",fromText:"يبدا",toText:"ينتهي",wholeText:"كامل",fractionText:"جزء",unitText:"وحدة",delimiter:"/",decimalSeparator:".",thousandsSeparator:",",labels:["سنوات","أشهر","أيام","ساعة","دقائق","ثواني",""],labelsShort:["سنوات","أشهر","أيام","ساعة","دقائق","ثواني",""],startText:"بدء",stopText:"إيقاف",resetText:"إعادة ضبط",lapText:"الدورة",hideText:"إخفاء",offText:"إيقاف",onText:"تشغيل",backText:"رجوع",undoText:"تراجع"},b.i18n.bg={setText:"Задаване",cancelText:"Отмяна",clearText:"Изчистване",selectedText:"{count} подбран",dateFormat:"dd.mm.yy",dayNames:["Неделя","Понеделник","Вторник","Сряда","Четвъртък","Петък","Събота"],dayNamesShort:["Нед","Пон","Вто","Сря","Чет","Пет","Съб"],dayNamesMin:["Не","По","Вт","Ср","Че","Пе","Съ"],dayText:"ден",delimiter:".",hourText:"час",minuteText:"минута",monthNames:["Януари","Февруари","Март","Април","Май","Юни","Юли","Август","Септември","Октомври","Ноември","Декември"],monthNamesShort:["Яну","Фев","Мар","Апр","Май","Юни","Юли","Авг","Сеп","Окт","Нов","Дек"],monthText:"месец",secText:"секунди",timeFormat:"H:ii",yearText:"година",nowText:"Сега",pmText:"pm",amText:"am",firstDay:1,dateText:"Дата",timeText:"път",todayText:"днес",prevMonthText:"Предишния месец",nextMonthText:"Следващият месец",prevYearText:"Предходната година",nextYearText:"Следващата година",closeText:"затвори",eventText:"Събитие",eventsText:"Събития",allDayText:"Цял ден",noEventsText:"Няма събития",moreEventsText:"Още {count}",fromText:"ОТ",toText:"ДО",wholeText:"цяло",fractionText:"фракция",unitText:"единица",labels:["Години","месеца","дни","часа","минути","секунди",""],labelsShort:["Години","месеца","дни","часа","минути","секунди",""],startText:"Старт",stopText:"Стоп",resetText:"Нулиране",lapText:"Обиколка",hideText:"крия",backText:"връщане",undoText:"ОТМЯНА",offText:"ИЗКЛ",onText:"ВКЛ",decimalSeparator:",",thousandsSeparator:" "},b.i18n.ca={setText:"Acceptar",cancelText:"Cancel·lar",clearText:"Esborrar",selectedText:"{count} seleccionat",selectedPluralText:"{count} seleccionats",dateFormat:"dd/mm/yy",dayNames:["Diumenge","Dilluns","Dimarts","Dimecres","Dijous","Divendres","Dissabte"],dayNamesShort:["Dg","Dl","Dt","Dc","Dj","Dv","Ds"],dayNamesMin:["Dg","Dl","Dt","Dc","Dj","Dv","Ds"],dayText:"Dia",hourText:"Hores",minuteText:"Minuts",monthNames:["Gener","Febrer","Març","Abril","Maig","Juny","Juliol","Agost","Setembre","Octubre","Novembre","Desembre"],monthNamesShort:["Gen","Feb","Mar","Abr","Mai","Jun","Jul","Ago","Set","Oct","Nov","Des"],monthText:"Mes",secText:"Segons",timeFormat:"HH:ii",yearText:"Any",nowText:"Ara",pmText:"pm",amText:"am",todayText:"Avui",firstDay:1,dateText:"Data",timeText:"Temps",closeText:"Tancar",allDayText:"Tot el dia",noEventsText:"Cap esdeveniment",eventText:"Esdeveniments",eventsText:"Esdeveniments",moreEventsText:"{count} més",fromText:"Iniciar",toText:"Final",wholeText:"Sencer",fractionText:"Fracció",unitText:"Unitat",labels:["Anys","Mesos","Dies","Hores","Minuts","Segons",""],labelsShort:["Anys","Mesos","Dies","Hrs","Mins","Secs",""],startText:"Iniciar",stopText:"Aturar",resetText:"Reiniciar",lapText:"Volta",hideText:"Amagar",backText:"Enrere",undoText:"Desfés",offText:"No",onText:"Si"},b.i18n.cs={setText:"Zadej",cancelText:"Storno",clearText:"Vymazat",selectedText:"Označený: {count}",dateFormat:"dd.mm.yy",dayNames:["Neděle","Pondělí","Úterý","Středa","Čtvrtek","Pátek","Sobota"],dayNamesShort:["Ne","Po","Út","St","Čt","Pá","So"],dayNamesMin:["N","P","Ú","S","Č","P","S"],dayText:"Den",hourText:"Hodiny",minuteText:"Minuty",monthNames:["Leden","Únor","Březen","Duben","Květen","Červen","Červenec","Srpen","Září","Říjen","Listopad","Prosinec"],monthNamesShort:["Led","Úno","Bře","Dub","Kvě","Čer","Čvc","Spr","Zář","Říj","Lis","Pro"],monthText:"Měsíc",secText:"Sekundy",timeFormat:"HH:ii",yearText:"Rok",nowText:"Teď",amText:"am",pmText:"pm",todayText:"Dnes",firstDay:1,dateText:"Datum",timeText:"Čas",closeText:"Zavřít",allDayText:"Celý den",noEventsText:"Žádné události",eventText:"Událostí",eventsText:"Události",moreEventsText:"{count} další",fromText:"Začátek",toText:"Konec",wholeText:"Celý",fractionText:"Část",unitText:"Jednotka",labels:["Roky","Měsíce","Dny","Hodiny","Minuty","Sekundy",""],labelsShort:["Rok","Měs","Dny","Hod","Min","Sec",""],startText:"Start",stopText:"Stop",resetText:"Resetovat",lapText:"Etapa",hideText:"Schovat",backText:"Zpět",undoText:"Zpět",offText:"O",onText:"I",decimalSeparator:",",thousandsSeparator:" "},b.i18n.da={setText:"Sæt",cancelText:"Annuller",clearText:"Ryd",selectedText:"{count} valgt",selectedPluralText:"{count} valgt",dateFormat:"dd/mm/yy",dayNames:["Søndag","Mandag","Tirsdag","Onsdag","Torsdag","Fredag","Lørdag"],dayNamesShort:["Søn","Man","Tir","Ons","Tor","Fre","Lør"],dayNamesMin:["S","M","T","O","T","F","L"],dayText:"Dag",hourText:"Timer",minuteText:"Minutter",monthNames:["Januar","Februar","Marts","April","Maj","Juni","Juli","August","September","Oktober","November","December"],monthNamesShort:["Jan","Feb","Mar","Apr","Maj","Jun","Jul","Aug","Sep","Okt","Nov","Dec"],monthText:"Måned",secText:"Sekunder",amText:"am",pmText:"pm",timeFormat:"HH.ii",yearText:"År",nowText:"Nu",todayText:"I dag",firstDay:1,dateText:"Dato",timeText:"Tid",closeText:"Luk",allDayText:"Hele dagen",noEventsText:"Ingen begivenheder",eventText:"Begivenheder",eventsText:"Begivenheder",moreEventsText:"{count} mere",fromText:"Start",toText:"Slut",wholeText:"Hele",fractionText:"Dele",unitText:"Enhed",labels:["År","Måneder","Dage","Timer","Minutter","Sekunder",""],labelsShort:["År","Mdr","Dg","Timer","Min","Sek",""],startText:"Start",stopText:"Stop",resetText:"Nulstil",lapText:"Omgang",hideText:"Skjul",offText:"Fra",onText:"Til",backText:"Tilbage",undoText:"Fortryd"},b.i18n.de={setText:"OK",cancelText:"Abbrechen",clearText:"Löschen",selectedText:"{count} ausgewählt",dateFormat:"dd.mm.yy",dayNames:["Sonntag","Montag","Dienstag","Mittwoch","Donnerstag","Freitag","Samstag"],dayNamesShort:["So","Mo","Di","Mi","Do","Fr","Sa"],dayNamesMin:["S","M","D","M","D","F","S"],dayText:"Tag",delimiter:".",hourText:"Stunde",minuteText:"Minuten",monthNames:["Januar","Februar","März","April","Mai","Juni","Juli","August","September","Oktober","November","Dezember"],monthNamesShort:["Jan","Feb","Mär","Apr","Mai","Jun","Jul","Aug","Sep","Okt","Nov","Dez"],monthText:"Monat",secText:"Sekunden",timeFormat:"HH:ii",yearText:"Jahr",nowText:"Jetzt",pmText:"pm",amText:"am",todayText:"Heute",firstDay:1,dateText:"Datum",timeText:"Zeit",closeText:"Schließen",allDayText:"Ganztägig",noEventsText:"Keine Ereignisse",eventText:"Ereignis",eventsText:"Ereignisse",moreEventsText:"{count} weiteres Element",moreEventsPluralText:"{count} weitere Elemente",fromText:"Von",toText:"Bis",wholeText:"Ganze Zahl",fractionText:"Bruchzahl",unitText:"Maßeinheit",labels:["Jahre","Monate","Tage","Stunden","Minuten","Sekunden",""],labelsShort:["Jahr.","Mon.","Tag.","Std.","Min.","Sek.",""],startText:"Starten",stopText:"Stoppen",resetText:"Zurücksetzen",lapText:"Lap",hideText:"Ausblenden",backText:"Zurück",undoText:"Rückgängig machen",offText:"Aus",onText:"Ein",decimalSeparator:",",thousandsSeparator:" "},b.i18n.el={setText:"Ορισμος",cancelText:"Ακυρωση",clearText:"Διαγραφη",selectedText:"{count} επιλεγμένα",dateFormat:"dd/mm/yy",dayNames:["Κυριακή","Δευτέρα","Τρίτη","Τετάρτη","Πέμπτη","Παρασκευή","Σάββατο"],dayNamesShort:["Κυρ","Δευ","Τρι","Τετ","Πεμ","Παρ","Σαβ"],dayNamesMin:["Κυ","Δε","Τρ","Τε","Πε","Πα","Σα"],dayText:"ημέρα",delimiter:"/",hourText:"ώρα",minuteText:"λεπτό",monthNames:["Ιανουάριος","Φεβρουάριος","Μάρτιος","Απρίλιος","Μάιος","Ιούνιος","Ιούλιος","Αύγουστος","Σεπτέμβριος","Οκτώβριος","Νοέμβριος","Δεκέμβριος"],monthNamesShort:["Ιαν","Φεβ","Μαρ","Απρ","Μαι","Ιουν","Ιουλ","Αυγ","Σεπ","Οκτ","Νοε","Δεκ"],monthText:"Μήνας",secText:"δευτερόλεπτα",timeFormat:"H:ii",yearText:"έτος",nowText:"τώρα",pmText:"μμ",amText:"πμ",firstDay:1,dateText:"Ημερομηνία",timeText:"φορά",todayText:"Σήμερα",prevMonthText:"Προηγούμενο μήνα",nextMonthText:"Επόμενο μήνα",prevYearText:"Προηγούμενο έτος",nextYearText:"Επόμενο έτος",closeText:"Κλείσιμο",eventText:"Γεγονότα",eventsText:"Γεγονότα",allDayText:"Ολοήμερο",noEventsText:"Δεν υπάρχουν γεγονότα",moreEventsText:"{count} ακόμη",fromText:"Αρχή",toText:"Τέλος",wholeText:"Ολόκληρος",fractionText:"κλάσμα",unitText:"Μονάδα",labels:["Χρόνια","Μήνες","Ημέρες","Ωρες","Λεπτά","δευτερόλεπτα",""],labelsShort:["Χρόνια","Μήνες","Ημέρες","Ωρες","Λεπτά","δευτ",""],startText:"΄Εναρξη",stopText:"Διακοπή",resetText:"Επαναφορά",lapText:"Γύρος",hideText:"κρύβω",backText:"Πίσω",undoText:"Αναιρεση",offText:"Ανενεργό",onText:"Ενεργό",decimalSeparator:",",thousandsSeparator:" "},b.i18n["en-GB"]=b.i18n["en-UK"]={dateFormat:"dd/mm/yy",timeFormat:"HH:ii"},b.i18n.es={setText:"Aceptar",cancelText:"Cancelar",clearText:"Borrar",selectedText:"{count} seleccionado",selectedPluralText:"{count} seleccionados",dateFormat:"dd/mm/yy",dayNames:["Domingo","Lunes","Martes","Miércoles","Jueves","Viernes","Sábado"],dayNamesShort:["Do","Lu","Ma","Mi","Ju","Vi","Sá"],dayNamesMin:["D","L","M","M","J","V","S"],dayText:"Día",hourText:"Horas",minuteText:"Minutos",monthNames:["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"],monthNamesShort:["Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Sep","Oct","Nov","Dic"],monthText:"Mes",secText:"Segundos",timeFormat:"HH:ii",yearText:"A&ntilde;o",nowText:"Ahora",pmText:"pm",amText:"am",todayText:"Hoy",firstDay:1,dateText:"Fecha",timeText:"Tiempo",closeText:"Cerrar",allDayText:"Todo el día",noEventsText:"No hay eventos",eventText:"Evento",eventsText:"Eventos",moreEventsText:"{count} más",fromText:"Iniciar",toText:"Final",wholeText:"Entero",fractionText:"Fracción",unitText:"Unidad",labels:["Años","Meses","Días","Horas","Minutos","Segundos",""],labelsShort:["Año","Mes","Día","Hora","Min","Seg",""],startText:"Iniciar",stopText:"Deténgase",resetText:"Reinicializar",lapText:"Lap",hideText:"Esconder",backText:"Atrás",undoText:"Deshacer",offText:"No",onText:"Sí",decimalSeparator:",",thousandsSeparator:" "},aR=[31,28,31,30,31,30,31,31,30,31,30,31],aD=[31,31,31,31,31,31,30,30,30,30,30,29];function bX(e,g,h){var b,c=(e=parseInt(e))-1600,f=(g=parseInt(g))-1,j=(h=parseInt(h))-1,d=365*c+parseInt((3+c)/4)-parseInt((99+c)/100)+parseInt((399+c)/400),a,k,i;for(b=0;b<f;++b)d+=aR[b];1<f&&(c%4==0&&c%100!=0||c%400==0)&&++d,a=(d+=j)-79,k=parseInt(a/12053),a%=12053,i=979+33*k+4*parseInt(a/1461);for(366<=(a%=1461)&&(i+=parseInt((a-1)/365),a=(a-1)%365),b=0;b<11&&a>=aD[b];++b)a-=aD[b];return[i,b+1,a+1]}aV.jalali={getYear:function(a){return bX(a.getFullYear(),a.getMonth()+1,a.getDate())[0]},getMonth:function(a){return--bX(a.getFullYear(),a.getMonth()+1,a.getDate())[1]},getDay:function(a){return bX(a.getFullYear(),a.getMonth()+1,a.getDate())[2]},getDate:function(b,a,d,e,f,g,h){a<0&&(b+=Math.floor(a/12),a=12+a%12),11<a&&(b+=Math.floor(a/12),a%=12);var c=function(f,g,i){var b,e=(f=parseInt(f))-979,j=(g=parseInt(g))-1,k=(i=parseInt(i))-1,h=365*e+8*parseInt(e/33)+parseInt((e%33+3)/4),a,d,c;for(b=0;b<j;++b)h+=aD[b];a=(h+=k)+79,d=1600+400*parseInt(a/146097),c=!0;for(36525<=(a%=146097)&&(a--,d+=100*parseInt(a/36524),365<=(a%=36524)?a++:c=!1),d+=4*parseInt(a/1461),366<=(a%=1461)&&(c=!1,a--,d+=parseInt(a/365),a%=365),b=0;aR[b]+(1==b&&c)<=a;b++)a-=aR[b]+(1==b&&c);return[d,b+1,a+1]}(b,+a+1,d);return new Date(c[0],c[1]-1,c[2],e||0,f||0,g||0,h||0)},getMaxDayOfMonth:function(e,f){for(var b,a,c,d=31;!1==(a=f+1,c=d,!((b=e)<0||32767<b||a<1||12<a||c<1||c>aD[a-1]+(12==a&&(b-979)%33%4==0)));)d--;return d}},b.i18n.fa={setText:"تاييد",cancelText:"انصراف",clearText:"واضح ",selectedText:"{count} منتخب",calendarSystem:"jalali",dateFormat:"yy/mm/dd",dayNames:["يکشنبه","دوشنبه","سه‌شنبه","چهارشنبه","پنج‌شنبه","جمعه","شنبه"],dayNamesShort:["ی","د","س","چ","پ","ج","ش"],dayNamesMin:["ی","د","س","چ","پ","ج","ش"],dayText:"روز",hourText:"ساعت",minuteText:"دقيقه",monthNames:["فروردين","ارديبهشت","خرداد","تير","مرداد","شهريور","مهر","آبان","آذر","دی","بهمن","اسفند"],monthNamesShort:["فروردين","ارديبهشت","خرداد","تير","مرداد","شهريور","مهر","آبان","آذر","دی","بهمن","اسفند"],monthText:"ماه",secText:"ثانيه",timeFormat:"HH:ii",timeWheels:"iiHH",yearText:"سال",nowText:"اکنون",amText:"ب",pmText:"ص",todayText:"امروز",firstDay:6,rtl:!0,dateText:"تاریخ ",timeText:"زمان ",closeText:"نزدیک",allDayText:"تمام روز",noEventsText:"هیچ رویداد",eventText:"رویداد",eventsText:"رویدادها",moreEventsText:"{count} مورد دیگر",fromText:"شروع ",toText:"پایان",wholeText:"تمام",fractionText:"کسر",unitText:"واحد",labels:["سال","ماه","روز","ساعت","دقیقه","ثانیه",""],labelsShort:["سال","ماه","روز","ساعت","دقیقه","ثانیه",""],startText:"شروع",stopText:"پايان",resetText:"تنظیم مجدد",lapText:"Lap",hideText:"پنهان کردن",backText:"پشت",undoText:"واچیدن"},b.i18n.fi={setText:"Aseta",cancelText:"Peruuta",clearText:"Tyhjennä",selectedText:"{count} valita",dateFormat:"d. MM yy",dayNames:["Sunnuntai","Maanantai","Tiistai","Keskiviiko","Torstai","Perjantai","Lauantai"],dayNamesShort:["Su","Ma","Ti","Ke","To","Pe","La"],dayNamesMin:["S","M","T","K","T","P","L"],dayText:"Päivä",delimiter:".",hourText:"Tuntia",minuteText:"Minuutti",monthNames:["Tammikuu","Helmikuu","Maaliskuu","Huhtikuu","Toukokuu","Kesäkuu","Heinäkuu","Elokuu","Syyskuu","Lokakuu","Marraskuu","Joulukuu"],monthNamesShort:["Tam","Hel","Maa","Huh","Tou","Kes","Hei","Elo","Syy","Lok","Mar","Jou"],monthText:"Kuukausi",secText:"Sekunda",timeFormat:"H:ii",yearText:"Vuosi",nowText:"Nyt",pmText:"pm",amText:"am",firstDay:1,dateText:"Päiväys",timeText:"Aika",todayText:"Tänään",prevMonthText:"Edellinen kuukausi",nextMonthText:"Ensi kuussa",prevYearText:"Edellinen vuosi",nextYearText:"Ensi vuosi",closeText:"Sulje",eventText:"Tapahtumia",eventsText:"Tapahtumia",allDayText:"Koko päivä",noEventsText:"Ei tapahtumia",moreEventsText:"{count} muu",moreEventsPluralText:"{count} muuta",fromText:"Alkaa",toText:"Päättyy",wholeText:"Kokonainen",fractionText:"Murtoluku",unitText:"Yksikkö",labels:["Vuosi","Kuukausi","Päivä","Tunnin","Minuutti","sekuntia",""],labelsShort:["Vuo","Kuu","Päi","Tun","Min","Sek",""],startText:"Käynnistys",stopText:"Seis",resetText:"Aseta uudelleen",lapText:"Kierros",hideText:"Vuota",backText:"Edellinen",undoText:"Kumoa",offText:"Pois",onText:"Päällä",decimalSeparator:",",thousandsSeparator:" "},b.i18n.fr={setText:"Terminer",cancelText:"Annuler",clearText:"Effacer",selectedText:"{count} sélectionné",selectedPluralText:"{count} sélectionnés",dateFormat:"dd/mm/yy",dayNames:["Dimanche","Lundi","Mardi","Mercredi","Jeudi","Vendredi","Samedi"],dayNamesShort:["Dim.","Lun.","Mar.","Mer.","Jeu.","Ven.","Sam."],dayNamesMin:["D","L","M","M","J","V","S"],dayText:"Jour",monthText:"Mois",monthNames:["Janvier","Février","Mars","Avril","Mai","Juin","Juillet","Août","Septembre","Octobre","Novembre","Décembre"],monthNamesShort:["Janv.","Févr.","Mars","Avril","Mai","Juin","Juil.","Août","Sept.","Oct.","Nov.","Déc."],hourText:"Heures",minuteText:"Minutes",secText:"Secondes",timeFormat:"HH:ii",yearText:"Année",nowText:"Maintenant",pmText:"pm",amText:"am",todayText:"Aujourd'hui",firstDay:1,dateText:"Date",timeText:"Heure",closeText:"Fermer",allDayText:"Toute la journée",noEventsText:"Aucun événement",eventText:"Événement",eventsText:"Événements",moreEventsText:"{count} autre",moreEventsPluralText:"{count} autres",fromText:"Démarrer",toText:"Fin",wholeText:"Entier",fractionText:"Fraction",unitText:"Unité",labels:["Ans","Mois","Jours","Heures","Minutes","Secondes",""],labelsShort:["Ans","Mois","Jours","Hrs","Min","Sec",""],startText:"Démarrer",stopText:"Arrêter",resetText:"Réinitialiser",lapText:"Lap",hideText:"Cachez",backText:"Retour",undoText:"Annuler",offText:"Non",onText:"Oui",decimalSeparator:",",thousandsSeparator:" "},b.i18n.he={rtl:!0,setText:"שמירה",cancelText:"ביטול",clearText:"נקה",selectedText:"{count} נבחר",selectedPluralText:"{count} נבחרו",dateFormat:"dd/mm/yy",dayNames:["ראשון","שני","שלישי","רביעי","חמישי","שישי","שבת"],dayNamesShort:["א'","ב'","ג'","ד'","ה'","ו'","ש'"],dayNamesMin:["א","ב","ג","ד","ה","ו","ש"],dayText:"יום",hourText:"שעות",minuteText:"דקות",monthNames:["ינואר","פברואר","מרץ","אפריל","מאי","יוני","יולי","אוגוסט","ספטמבר","אוקטובר","נובמבר","דצמבר"],monthNamesShort:["ינו","פבר","מרץ","אפר","מאי","יונ","יול","אוג","ספט","אוק","נוב","דצמ"],monthText:"חודש",secText:"שניות",amText:"am",pmText:"pm",timeFormat:"HH:ii",timeWheels:"iiHH",yearText:"שנה",nowText:"עכשיו",firstDay:0,dateText:"תאריך",timeText:"זמן",closeText:"סגירה",todayText:"היום",allDayText:"כל היום",noEventsText:"אין אירועים",eventText:"מִקרֶה",eventsText:"מִקרֶה",moreEventsText:"אירוע אחד נוסף",moreEventsPluralText:"{count} אירועים נוספים",fromText:"התחלה",toText:"סיום",wholeText:"כֹּל",fractionText:"שבריר",unitText:"יחידה",labels:["שנים","חודשים","ימים","שעות","דקות","שניים",""],labelsShort:["שנים","חודשים","ימים","שעות","דקות","שניים",""],startText:"התחל",stopText:"עצור",resetText:"אתחול",lapText:"הקפה",hideText:"הסתר",offText:"כיבוי",onText:"הפעלה",backText:"חזור",undoText:"ביטול פעולה"},b.i18n.hi={setText:"सैट करें",cancelText:"रद्द करें",clearText:"साफ़ को",selectedText:"{count} चयनित",dateFormat:"dd/mm/yy",dayNames:["रविवार","सोमवार","मंगलवार","बुधवार","गुरुवार","शुक्रवार","शनिवार"],dayNamesShort:["रवि","सोम","मंगल","बुध","गुरु","शुक्र","शनि"],dayNamesMin:["रवि","सोम","मंगल","बुध","गुरु","शुक्र","शनि"],dayText:"दिन",delimiter:".",hourText:"घंटा",minuteText:"मिनट",monthNames:["जनवरी ","फरवरी","मार्च","अप्रेल","मई","जून","जूलाई","अगस्त ","सितम्बर","अक्टूबर","नवम्बर","दिसम्बर"],monthNamesShort:["जन","फर","मार्च","अप्रेल","मई","जून","जूलाई","अग","सित","अक्ट","नव","दि"],monthText:"महीना",secText:"सेकंड",timeFormat:"H:ii",yearText:"साल",nowText:"अब",pmText:"अपराह्न",amText:"पूर्वाह्न",firstDay:1,dateText:"तिथि",timeText:"समय",todayText:"आज",prevMonthText:"पिछ्ला महिना",nextMonthText:"अगले महीने",prevYearText:"पिछला साल",nextYearText:"अगले वर्ष",closeText:"बंद",eventText:"इवेट३",eventsText:"इवेट३",allDayText:"पूरे दिन",noEventsText:"Ei tapahtumia",moreEventsText:"{count} और",fromText:"से",toText:"तक",wholeText:"समूचा",fractionText:"अंश",unitText:"इकाई",labels:["साल","महीने","दिन","घंटे","मिनट","सेकंड",""],labelsShort:["साल","महीने","दिन","घंटे","मिनट","सेकंड",""],startText:"प्रारंभ",stopText:"रोकें",resetText:"रीसेट करें",lapText:"लैप",hideText:"छिपाना",backText:"वापस",undoText:"वापस लाएं",offText:"बंद",onText:"चालू",decimalSeparator:",",thousandsSeparator:" "},b.i18n.hr={setText:"Postavi",cancelText:"Izlaz",clearText:"Izbriši",selectedText:"{count} odabran",dateFormat:"dd.mm.yy",dayNames:["Nedjelja","Ponedjeljak","Utorak","Srijeda","Četvrtak","Petak","Subota"],dayNamesShort:["Ned","Pon","Uto","Sri","Čet","Pet","Sub"],dayNamesMin:["Ne","Po","Ut","Sr","Če","Pe","Su"],dayText:"Dan",delimiter:".",hourText:"Sat",minuteText:"Minuta",monthNames:["Siječanj","Veljača","Ožujak","Travanj","Svibanj","Lipanj","Srpanj","Kolovoz","Rujan","Listopad","Studeni","Prosinac"],monthNamesShort:["Sij","Velj","Ožu","Tra","Svi","Lip","Srp","Kol","Ruj","Lis","Stu","Pro"],monthText:"Mjesec",secText:"Sekunda",timeFormat:"H:ii",yearText:"Godina",nowText:"Sada",pmText:"pm",amText:"am",firstDay:1,dateText:"Datum",timeText:"Vrijeme",todayText:"Danas",prevMonthText:"Prethodni mjesec",nextMonthText:"Sljedeći mjesec",prevYearText:"Prethodni godina",nextYearText:"Slijedeće godine",closeText:"Zatvori",eventText:"Događaj",eventsText:"događaja",allDayText:"Cijeli dan",noEventsText:"Bez događaja",moreEventsText:"Još {count}",fromText:"Počinje",toText:"Završava",wholeText:"Cjelina",fractionText:"Frakcija",unitText:"Jedinica",labels:["godina","mjesec","dan","sat","minuta","sekunda",""],labelsShort:["god","mje","dan","sat","min","sec",""],startText:"Početak",stopText:"Prekid",resetText:"Resetiraj",lapText:"Ciklus",hideText:"Sakriti",backText:"Natrag",undoText:"Poništavanje",offText:"Uklj.",onText:"Isklj.",decimalSeparator:",",thousandsSeparator:" "},b.i18n.hu={setText:"OK",cancelText:"Mégse",clearText:"Törlés",selectedText:"{count} kiválasztva",dateFormat:"yy.mm.dd.",dayNames:["Vasárnap","Hétfő","Kedd","Szerda","Csütörtök","Péntek","Szombat"],dayNamesShort:["Va","Hé","Ke","Sze","Csü","Pé","Szo"],dayNamesMin:["V","H","K","Sz","Cs","P","Sz"],dayText:"Nap",delimiter:".",hourText:"Óra",minuteText:"Perc",monthNames:["Január","Február","Március","Április","Május","Június","Július","Augusztus","Szeptember","Október","November","December"],monthNamesShort:["Jan","Feb","Már","Ápr","Máj","Jún","Júl","Aug","Szep","Okt","Nov","Dec"],monthText:"Hónap",secText:"Másodperc",timeFormat:"H:ii",yearText:"Év",nowText:"Most",pmText:"pm",amText:"am",firstDay:1,dateText:"Dátum",timeText:"Idő",todayText:"Ma",prevMonthText:"Előző hónap",nextMonthText:"Következő hónap",prevYearText:"Előző év",nextYearText:"Következő év",closeText:"Bezár",eventText:"esemény",eventsText:"esemény",allDayText:"Egész nap",noEventsText:"Nincs esemény",moreEventsText:"{count} további",fromText:"Eleje",toText:"Vége",wholeText:"Egész",fractionText:"Tört",unitText:"Egység",labels:["Év","Hónap","Nap","Óra","Perc","Másodperc",""],labelsShort:["Év","Hó.","Nap","Óra","Perc","Mp.",""],startText:"Indít",stopText:"Megállít",resetText:"Visszaállít",lapText:"Lap",hideText:"Elrejt",backText:"Vissza",undoText:"Visszavon",offText:"Ki",onText:"Be",decimalSeparator:",",thousandsSeparator:" "},b.i18n.it={setText:"OK",cancelText:"Annulla",clearText:"Chiarire",selectedText:"{count} selezionato",selectedPluralText:"{count} selezionati",dateFormat:"dd/mm/yy",dayNames:["Domenica","Lunedì","Mertedì","Mercoledì","Giovedì","Venerdì","Sabato"],dayNamesShort:["Do","Lu","Ma","Me","Gi","Ve","Sa"],dayNamesMin:["D","L","M","M","G","V","S"],dayText:"Giorno",hourText:"Ore",minuteText:"Minuti",monthNames:["Gennaio","Febbraio","Marzo","Aprile","Maggio","Giugno","Luglio","Agosto","Settembre","Ottobre","Novembre","Dicembre"],monthNamesShort:["Gen","Feb","Mar","Apr","Mag","Giu","Lug","Ago","Set","Ott","Nov","Dic"],monthText:"Mese",secText:"Secondi",timeFormat:"HH:ii",yearText:"Anno",nowText:"Ora",pmText:"pm",amText:"am",todayText:"Oggi",firstDay:1,dateText:"Data",timeText:"Volta",closeText:"Chiudere",allDayText:"Tutto il giorno",noEventsText:"Nessun evento",eventText:"Evento",eventsText:"Eventi",moreEventsText:"{count} altro",moreEventsPluralText:"altri {count}",fromText:"Inizio",toText:"Fine",wholeText:"Intero",fractionText:"Frazione",unitText:"Unità",labels:["Anni","Mesi","Giorni","Ore","Minuti","Secondi",""],labelsShort:["Anni","Mesi","Gio","Ore","Min","Sec",""],startText:"Inizio",stopText:"Arresto",resetText:"Ripristina",lapText:"Lap",hideText:"Nascondi",backText:"Indietro",undoText:"Annulla",offText:"Via",onText:"Su",decimalSeparator:",",thousandsSeparator:" "},b.i18n.ja={setText:"セット",cancelText:"キャンセル",clearText:"クリア",selectedText:"{count} 選択",dateFormat:"yy年mm月dd日",dayNames:["日","月","火","水","木","金","土"],dayNamesShort:["日","月","火","水","木","金","土"],dayNamesMin:["日","月","火","水","木","金","土"],dayText:"日",hourText:"時",minuteText:"分",monthNames:["1月","2月","3月","4月","5月","6月","7月","8月","9月","10月","11月","12月"],monthNamesShort:["1月","2月","3月","4月","5月","6月","7月","8月","9月","10月","11月","12月"],monthText:"月",secText:"秒",timeFormat:"HH:ii",yearText:"年",nowText:"今",pmText:"午後",amText:"午前",yearSuffix:"年",monthSuffix:"月",daySuffix:"日",todayText:"今日",dateText:"日付",timeText:"時間",closeText:"クローズ",allDayText:"終日",noEventsText:"イベントはありません",eventText:"イベント",eventsText:"イベント",moreEventsText:"他 {count} 件",fromText:"開始",toText:"終わり",wholeText:"全数",fractionText:"分数",unitText:"単位",labels:["年間","月間","日間","時間","分","秒",""],labelsShort:["年間","月間","日間","時間","分","秒",""],startText:"開始",stopText:"停止",resetText:"リセット",lapText:"ラップ",hideText:"隠す",backText:"バック",undoText:"アンドゥ"},b.i18n.ko={setText:"설정",cancelText:"취소",clearText:"삭제",selectedText:"{count} 선택된",dateFormat:"yy년mm월dd일",dayNames:["일요일","월요일","화요일","수요일","목요일","금요일","토요일"],dayNamesShort:["일","월","화","수","목","금","토"],dayNamesMin:["일","월","화","수","목","금","토"],dayText:"일",delimiter:"-",hourText:"시간",minuteText:"분",monthNames:["1월","2월","3월","4월","5월","6월","7월","8월","9월","10월","11월","12월"],monthNamesShort:["1월","2월","3월","4월","5월","6월","7월","8월","9월","10월","11월","12월"],monthText:"달",secText:"초",timeFormat:"H:ii",yearText:"년",nowText:"지금",pmText:"오후",amText:"오전",yearSuffix:"년",monthSuffix:"월",daySuffix:"일",firstDay:0,dateText:"날짜",timeText:"시간",todayText:"오늘",prevMonthText:"이전 달",nextMonthText:"다음 달",prevYearText:"이전 년",nextYearText:"다음 년",closeText:"닫기",eventText:"이벤트",eventsText:"이벤트",allDayText:"종일",noEventsText:"이벤트 없음",moreEventsText:"{count}개 더보기",fromText:"시작",toText:"종료",wholeText:"정수",fractionText:"분수",unitText:"단위",labels:["년","달","일","시간","분","초",""],labelsShort:["년","달","일","시간","분","초",""],startText:"시작",stopText:"중지 ",resetText:"초기화",lapText:"기록",hideText:"숨는 장소",backText:"뒤로",undoText:"실행취소",offText:"끔",onText:"켬",decimalSeparator:",",thousandsSeparator:" "},b.i18n.lt={setText:"OK",cancelText:"Atšaukti",clearText:"Išvalyti",selectedText:"Pasirinktas {count}",selectedPluralText:"Pasirinkti {count}",dateFormat:"yy-mm-dd",dayNames:["Sekmadienis","Pirmadienis","Antradienis","Trečiadienis","Ketvirtadienis","Penktadienis","Šeštadienis"],dayNamesShort:["S","Pr","A","T","K","Pn","Š"],dayNamesMin:["S","Pr","A","T","K","Pn","Š"],dayText:"Diena",hourText:"Valanda",minuteText:"Minutes",monthNames:["Sausis","Vasaris","Kovas","Balandis","Gegužė","Birželis","Liepa","Rugpjūtis","Rugsėjis","Spalis","Lapkritis","Gruodis"],monthNamesShort:["Sau","Vas","Kov","Bal","Geg","Bir","Lie","Rugp","Rugs","Spa","Lap","Gruo"],monthText:"Mėnuo",secText:"Sekundes",amText:"am",pmText:"pm",timeFormat:"HH:ii",yearText:"Metai",nowText:"Dabar",todayText:"Šiandien",firstDay:1,dateText:"Data",timeText:"Laikas",closeText:"Uždaryti",allDayText:"Visą dieną",noEventsText:"Nėra įvykių",eventText:"Įvykių",eventsText:"Įvykiai",moreEventsText:"Dar {count}",fromText:"Nuo",toText:"Iki",wholeText:"Visas",fractionText:"Frakcija",unitText:"Vienetas",labels:["Metai","Mėnesiai","Dienos","Valandos","Minutes","Sekundes",""],labelsShort:["m","mėn.","d","h","min","s",""],startText:"Pradėti",stopText:"Sustabdyti",resetText:"Išnaujo",lapText:"Ratas",hideText:"Slėpti",backText:"Atgal",undoText:"Anuliuoti",offText:"Išj.",onText:"Įj.",decimalSeparator:",",thousandsSeparator:" "},b.i18n.nl={setText:"Instellen",cancelText:"Annuleren",clearText:"Leegmaken",selectedText:"{count} gekozen",dateFormat:"dd-mm-yy",dayNames:["Zondag","Maandag","Dinsdag","Woensdag","Donderdag","Vrijdag","Zaterdag"],dayNamesShort:["zo","ma","di","wo","do","vr","za"],dayNamesMin:["z","m","d","w","d","v","z"],dayText:"Dag",hourText:"Uur",minuteText:"Minuten",monthNames:["januari","februari","maart","april","mei","juni","juli","augustus","september","oktober","november","december"],monthNamesShort:["jan","feb","mrt","apr","mei","jun","jul","aug","sep","okt","nov","dec"],monthText:"Maand",secText:"Seconden",timeFormat:"HH:ii",yearText:"Jaar",nowText:"Nu",pmText:"pm",amText:"am",todayText:"Vandaag",firstDay:1,dateText:"Datum",timeText:"Tijd",closeText:"Sluiten",allDayText:"Hele dag",noEventsText:"Geen activiteiten",eventText:"Activiteit",eventsText:"Activiteiten",moreEventsText:"nog {count}",fromText:"Start",toText:"Einde",wholeText:"geheel",fractionText:"fractie",unitText:"eenheid",labels:["Jaren","Maanden","Dagen","Uren","Minuten","Seconden",""],labelsShort:["j","m","d","u","min","sec",""],startText:"Start",stopText:"Stop",resetText:"Reset",lapText:"Ronde",hideText:"Verbergen",backText:"Terug",undoText:"Onged. maken",offText:"Uit",onText:"Aan",decimalSeparator:",",thousandsSeparator:" "},b.i18n.no={setText:"OK",cancelText:"Avbryt",clearText:"Tømme",selectedText:"{count} valgt",dateFormat:"dd.mm.yy",dayNames:["Søndag","Mandag","Tirsdag","Onsdag","Torsdag","Fredag","Lørdag"],dayNamesShort:["Sø","Ma","Ti","On","To","Fr","Lø"],dayNamesMin:["S","M","T","O","T","F","L"],dayText:"Dag",delimiter:".",hourText:"Time",minuteText:"Minutt",monthNames:["Januar","Februar","Mars","April","Mai","Juni","Juli","August","September","Oktober","November","Desember"],monthNamesShort:["Jan","Feb","Mar","Apr","Mai","Jun","Jul","Aug","Sep","Okt","Nov","Des"],monthText:"Måned",secText:"Sekund",timeFormat:"HH:ii",yearText:"År",nowText:"Nå",pmText:"pm",amText:"am",todayText:"I dag",firstDay:1,dateText:"Dato",timeText:"Tid",closeText:"Lukk",allDayText:"Hele dagen",noEventsText:"Ingen hendelser",eventText:"Hendelse",eventsText:"Hendelser",moreEventsText:"{count} mere",fromText:"Start",toText:"End",wholeText:"Hele",fractionText:"Fraksjon",unitText:"Enhet",labels:["År","Måneder","Dager","Timer","Minutter","Sekunder",""],labelsShort:["År","Mån","Dag","Time","Min","Sek",""],startText:"Start",stopText:"Stopp",resetText:"Tilbakestille",lapText:"Runde",hideText:"Skjul",backText:"Tilbake",undoText:"Angre",offText:"Av",onText:"På",decimalSeparator:",",thousandsSeparator:" "},b.i18n.pl={setText:"Zestaw",cancelText:"Anuluj",clearText:"Oczyścić",selectedText:"Wybór: {count}",dateFormat:"yy-mm-dd",dayNames:["Niedziela","Poniedziałek","Wtorek","Środa","Czwartek","Piątek","Sobota"],dayNamesShort:["Niedz.","Pon.","Wt.","Śr.","Czw.","Pt.","Sob."],dayNamesMin:["N","P","W","Ś","C","P","S"],dayText:"Dzień",hourText:"Godziny",minuteText:"Minuty",monthNames:["Styczeń","Luty","Marzec","Kwiecień","Maj","Czerwiec","Lipiec","Sierpień","Wrzesień","Październik","Listopad","Grudzień"],monthNamesShort:["Sty","Lut","Mar","Kwi","Maj","Cze","Lip","Sie","Wrz","Paź","Lis","Gru"],monthText:"Miesiąc",secText:"Sekundy",timeFormat:"HH:ii",yearText:"Rok",nowText:"Teraz",amText:"am",pmText:"pm",todayText:"Dzisiaj",firstDay:1,dateText:"Data",timeText:"Czas",closeText:"Zakończenie",allDayText:"Cały dzień",noEventsText:"Brak wydarzeń",eventText:"Wydarzeń",eventsText:"Wydarzenia",moreEventsText:"Jeszcze {count}",fromText:"Rozpoczęcie",toText:"Koniec",wholeText:"Cały",fractionText:"Ułamek",unitText:"Jednostka",labels:["Lata","Miesiąc","Dni","Godziny","Minuty","Sekundy",""],labelsShort:["R","M","Dz","Godz","Min","Sek",""],startText:"Rozpoczęcie",stopText:"Zatrzymać",resetText:"Zresetować",lapText:"Zakładka",hideText:"Ukryć",backText:"Wróć",undoText:"Cofnij",offText:"Wył",onText:"Wł",decimalSeparator:",",thousandsSeparator:" "},b.i18n["pt-BR"]={setText:"Selecionar",cancelText:"Cancelar",clearText:"Claro",selectedText:"{count} selecionado",selectedPluralText:"{count} selecionados",dateFormat:"dd/mm/yy",dayNames:["Domingo","Segunda-feira","Terça-feira","Quarta-feira","Quinta-feira","Sexta-feira","Sábado"],dayNamesShort:["Dom","Seg","Ter","Qua","Qui","Sex","Sáb"],dayNamesMin:["D","S","T","Q","Q","S","S"],dayText:"Dia",hourText:"Hora",minuteText:"Minutos",monthNames:["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"],monthNamesShort:["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"],monthText:"Mês",secText:"Segundo",timeFormat:"HH:ii",yearText:"Ano",nowText:"Agora",pmText:"pm",amText:"am",todayText:"Hoje",dateText:"Data",timeText:"Tempo",closeText:"Fechar",allDayText:"Dia inteiro",noEventsText:"Nenhum evento",eventText:"Evento",eventsText:"Eventos",moreEventsText:"Mais {count}",fromText:"In&iacute;cio",toText:"Fim",wholeText:"Inteiro",fractionText:"Fração",unitText:"Unidade",labels:["Anos","Meses","Dias","Horas","Minutos","Segundos",""],labelsShort:["Ano","M&ecirc;s","Dia","Hora","Min","Seg",""],startText:"Começar",stopText:"Pare",resetText:"Reinicializar",lapText:"Lap",hideText:"Esconder",backText:"Anterior",undoText:"Desfazer",offText:"Desl",onText:"Lig",decimalSeparator:",",thousandsSeparator:" "},b.i18n["pt-PT"]={setText:"Seleccionar",cancelText:"Cancelar",clearText:"Claro",selectedText:"{count} selecionado",selectedPluralText:"{count} selecionados",dateFormat:"dd-mm-yy",dayNames:["Domingo","Segunda-feira","Terça-feira","Quarta-feira","Quinta-feira","Sexta-feira","Sábado"],dayNamesShort:["Dom","Seg","Ter","Qua","Qui","Sex","Sáb"],dayNamesMin:["D","S","T","Q","Q","S","S"],dayText:"Dia",hourText:"Horas",minuteText:"Minutos",monthNames:["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"],monthNamesShort:["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"],monthText:"Mês",secText:"Segundo",timeFormat:"HH:ii",yearText:"Ano",nowText:"Actualizar",pmText:"pm",amText:"am",todayText:"Hoy",firstDay:1,dateText:"Data",timeText:"Tempo",closeText:"Fechar",allDayText:"Todo o dia",noEventsText:"Nenhum evento",eventText:"Evento",eventsText:"Eventos",moreEventsText:"mais {count}",fromText:"Início",toText:"Fim",wholeText:"Inteiro",fractionText:"Fracção",unitText:"Unidade",labels:["Anos","Meses","Dias","Horas","Minutos","Segundos",""],labelsShort:["Ano","Mês","Dia","Hora","Min","Seg",""],startText:"Começar",stopText:"Parar",resetText:"Reinicializar",lapText:"Lap",hideText:"Esconder",backText:"Anterior",undoText:"Anular",offText:"Desl",onText:"Lig",decimalSeparator:",",thousandsSeparator:" "},b.i18n.ro={setText:"Setare",cancelText:"Anulare",clearText:"Ştergere",selectedText:"{count} selectat",selectedPluralText:"{count} selectate",dateFormat:"dd.mm.yy",dayNames:["Duminică","Luni","Marți","Miercuri","Joi","Vineri","Sâmbătă"],dayNamesShort:["Du","Lu","Ma","Mi","Jo","Vi","Sâ"],dayNamesMin:["D","L","M","M","J","V","S"],dayText:" Ziua",delimiter:".",hourText:" Ore ",minuteText:"Minute",monthNames:["Ianuarie","Februarie","Martie","Aprilie","Mai","Iunie","Iulie","August","Septembrie","Octombrie","Noiembrie","Decembrie"],monthNamesShort:["Ian.","Feb.","Mar.","Apr.","Mai","Iun.","Iul.","Aug.","Sept.","Oct.","Nov.","Dec."],monthText:"Luna",secText:"Secunde",timeFormat:"HH:ii",yearText:"Anul",nowText:"Acum",amText:"am",pmText:"pm",todayText:"Astăzi",prevMonthText:"Luna anterioară",nextMonthText:"Luna următoare",prevYearText:"Anul anterior",nextYearText:"Anul următor",eventText:"Eveniment",eventsText:"Evenimente",allDayText:"Toată ziua",noEventsText:"Niciun eveniment",moreEventsText:"Încă unul",moreEventsPluralText:"Încă {count}",firstDay:1,dateText:"Data",timeText:"Ora",closeText:"Închidere",fromText:"Start",toText:"Final",wholeText:"Complet",fractionText:"Parţial",unitText:"Unitate",labels:["Ani","Luni","Zile","Ore","Minute","Secunde",""],labelsShort:["Ani","Luni","Zile","Ore","Min.","Sec.",""],startText:"Start",stopText:"Stop",resetText:"Resetare",lapText:"Tură",hideText:"Ascundere",backText:"Înapoi",undoText:"Anulează",offText:"Nu",onText:"Da",decimalSeparator:",",thousandsSeparator:" "},b.i18n["ru-UA"]={setText:"Установить",cancelText:"Отменить",clearText:"Очиститьr",selectedText:"{count} Вібрать",dateFormat:"dd.mm.yy",dayNames:["воскресенье","понедельник","вторник","среда","четверг","пятница","суббота"],dayNamesShort:["вс","пн","вт","ср","чт","пт","сб"],dayNamesMin:["в","п","в","с","ч","п","с"],dayText:"День",delimiter:".",hourText:"Часы",minuteText:"Минуты",monthNames:["Январь","Февраль","Март","Апрель","Май","Июнь","Июль","Август","Сентябрь","Октябрь","Ноябрь","Декабрь"],monthNamesShort:["Янв.","Февр.","Март","Апр.","Май","Июнь","Июль","Авг.","Сент.","Окт.","Нояб.","Дек."],monthText:"Месяцы",secText:"Сикунды",timeFormat:"HH:ii",yearText:"Год",nowText:"Сейчас",amText:"am",pmText:"pm",todayText:"Cегодня",firstDay:1,dateText:"Дата",timeText:"Время",closeText:"Закрыть",allDayText:"Весь день",noEventsText:"Нет событий",eventText:"Мероприятия",eventsText:"Мероприятия",moreEventsText:"Ещё {count}",fromText:"Начало",toText:"Конец",wholeText:"Весь",fractionText:"Часть",unitText:"Единица",labels:["Годы"," Месяцы "," Дни "," Часы "," Минуты "," Секунды",""],labelsShort:["Год","Мес.","Дн.","Ч.","Мин.","Сек.",""],startText:"Старт",stopText:"Стоп",resetText:" Сброс ",lapText:" Этап ",hideText:" Скрыть ",backText:"назад",undoText:"ОтменитЬ",offText:"O",onText:"I",decimalSeparator:",",thousandsSeparator:" "},b.i18n["ru-RU"]=b.i18n.ru={setText:"Установить",cancelText:"Отмена",clearText:"Очистить",selectedText:"{count} Выбрать",dateFormat:"dd.mm.yy",dayNames:["воскресенье","понедельник","вторник","среда","четверг","пятница","суббота"],dayNamesShort:["вс","пн","вт","ср","чт","пт","сб"],dayNamesMin:["в","п","в","с","ч","п","с"],dayText:"День",delimiter:".",hourText:"Час",minuteText:"Минут",monthNames:["Январь","Февраль","Март","Апрель","Май","Июнь","Июль","Август","Сентябрь","Октябрь","Ноябрь","Декабрь"],monthNamesShort:["Янв","Фев","Мар","Апр","Май","Июн","Июл","Авг","Сен","Окт","Ноя","Дек"],monthText:"Месяц",secText:"Секунд",timeFormat:"HH:ii",yearText:"Год",nowText:"Сейчас",amText:"am",pmText:"pm",todayText:"Cегодня",firstDay:1,dateText:"Дата",timeText:"Время",closeText:"Закрыть",allDayText:"Весь день",noEventsText:"Нет событий",eventText:"Мероприятия",eventsText:"Мероприятия",moreEventsText:"Ещё {count}",fromText:"Начало",toText:"Конец",wholeText:"Целое",fractionText:"Дробное",unitText:"Единица",labels:["Лет","Месяцев","Дней","Часов","Минут","Секунд",""],labelsShort:["Лет","Мес","Дн","Час","Мин","Сек",""],startText:"Старт",stopText:"Стоп",resetText:"Сбросить",lapText:"Круг",hideText:"Скрыть",backText:"назад",undoText:"ОтменитЬ",offText:"O",onText:"I",decimalSeparator:",",thousandsSeparator:" "},b.i18n.sk={setText:"Zadaj",cancelText:"Zrušiť",clearText:"Vymazať",selectedText:"Označený: {count}",dateFormat:"d.m.yy",dayNames:["Nedeľa","Pondelok","Utorok","Streda","Štvrtok","Piatok","Sobota"],dayNamesShort:["Ne","Po","Ut","St","Št","Pi","So"],dayNamesMin:["N","P","U","S","Š","P","S"],dayText:"Ďeň",hourText:"Hodiny",minuteText:"Minúty",monthNames:["Január","Február","Marec","Apríl","Máj","Jún","Júl","August","September","Október","November","December"],monthNamesShort:["Jan","Feb","Mar","Apr","Máj","Jún","Júl","Aug","Sep","Okt","Nov","Dec"],monthText:"Mesiac",secText:"Sekundy",timeFormat:"H:ii",yearText:"Rok",nowText:"Teraz",amText:"am",pmText:"pm",todayText:"Dnes",firstDay:1,dateText:"Datum",timeText:"Čas",closeText:"Zavrieť",allDayText:"Celý deň",noEventsText:"Žiadne udalosti",eventText:"Udalostí",eventsText:"Udalosti",moreEventsText:"{count} ďalšia",moreEventsPluralText:"{count} ďalšie",fromText:"Začiatok",toText:"Koniec",wholeText:"Celý",fractionText:"Časť",unitText:"Jednotka",labels:["Roky","Mesiace","Dni","Hodiny","Minúty","Sekundy",""],labelsShort:["Rok","Mes","Dni","Hod","Min","Sec",""],startText:"Start",stopText:"Stop",resetText:"Resetovať",lapText:"Etapa",hideText:"Schovať",backText:"Späť",undoText:"Späť",offText:"O",onText:"I",decimalSeparator:",",thousandsSeparator:" "},b.i18n.sr={setText:"Постави",cancelText:"Откажи",clearText:"Обриши",selectedText:"{count} изабрана",dateFormat:"dd.mm.yy",dayNames:["Недеља","Понедељак","Уторак","Среда","Четвртак","Петак","Субота"],dayNamesShort:["Нед","Пон","Уто","Сре","Чет","Пет","Суб"],dayNamesMin:["Не","По","Ут","Ср","Че","Пе","Су"],dayText:"Дан",delimiter:".",hourText:"Час",minuteText:"Минут",monthNames:["Јануар","Фебруар","Март","Април","Мај","Јун","Јул","Август","Септембар","Октобар","Новембар","Децембар"],monthNamesShort:["Јан","Феб","Мар","Апр","Мај","Јун","Јул","Авг","Сеп","Окт","Нов","Дец"],monthText:"месец",secText:"Секунд",timeFormat:"H:ii",yearText:"година",nowText:"сада",pmText:"pm",amText:"am",firstDay:1,dateText:"Датум",timeText:"време",todayText:"Данас",prevMonthText:"Претходни мјесец",nextMonthText:"Следећег месеца",prevYearText:"Претходна године",nextYearText:"Следеће године",closeText:"Затвори",eventText:"Догађај",eventsText:"Догађаји",allDayText:"Цео дан",noEventsText:"Нема догађаја",moreEventsText:"Још {count}",fromText:"Од",toText:"До",wholeText:"цео",fractionText:"Фракција",unitText:"единица",labels:["Године","Месеци","Дана","Сати","Минута","Секунди",""],labelsShort:["Год","Мес","Дана","Сати","Мину","Секу",""],startText:"Започни",stopText:"Стоп",resetText:"Ресетуј",lapText:"Круг",hideText:"Сакрити",backText:"Повратак",undoText:"Опозови",offText:"нe",onText:"да",decimalSeparator:",",thousandsSeparator:" "},b.i18n.sv={setText:"OK",cancelText:"Avbryt",clearText:"Klara",selectedText:"{count} vald",dateFormat:"yy-mm-dd",dayNames:["Söndag","Måndag","Tisdag","Onsdag","Torsdag","Fredag","Lördag"],dayNamesShort:["Sö","Må","Ti","On","To","Fr","Lö"],dayNamesMin:["S","M","T","O","T","F","L"],dayText:"Dag",hourText:"Timme",minuteText:"Minut",monthNames:["Januari","Februari","Mars","April","Maj","Juni","Juli","Augusti","September","Oktober","November","December"],monthNamesShort:["Jan","Feb","Mar","Apr","Maj","Jun","Jul","Aug","Sep","Okt","Nov","Dec"],monthText:"Månad",secText:"Sekund",timeFormat:"HH:ii",yearText:"År",nowText:"Nu",pmText:"pm",amText:"am",todayText:"I dag",firstDay:1,dateText:"Datum",timeText:"Tid",closeText:"Stäng",allDayText:"Heldag",noEventsText:"Inga aktiviteter",eventText:"Händelse",eventsText:"Händelser",moreEventsText:"{count} till",fromText:"Start",toText:"Slut",wholeText:"Hela",fractionText:"Bråk",unitText:"Enhet",labels:["År","Månader","Dagar","Timmar","Minuter","Sekunder",""],labelsShort:["År","Mån","Dag","Tim","Min","Sek",""],startText:"Start",stopText:"Stopp",resetText:"Återställ",lapText:"Varv",hideText:"Dölj",backText:"Tillbaka",undoText:"Ångra",offText:"Av",onText:"På"},b.i18n.th={setText:"ตั้งค่า",cancelText:"ยกเลิก",clearText:"ล้าง",selectedText:"{count} เลือก",dateFormat:"dd/mm/yy",dayNames:["อาทิตย์","จันทร์","อังคาร","พุธ","พฤหัสบดี","ศุกร์","เสาร์"],dayNamesShort:["อา.","จ.","อ.","พ.","พฤ.","ศ.","ส."],dayNamesMin:["อา.","จ.","อ.","พ.","พฤ.","ศ.","ส."],dayText:"วัน",delimiter:".",hourText:"ชั่วโมง",minuteText:"นาที",monthNames:["มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"],monthNamesShort:["ม.ค.","ก.พ.","มี.ค.","เม.ย.","พ.ค.","มิ.ย.","ก.ค.","ส.ค.","ก.ย.","ต.ค.","พ.ย.","ธ.ค."],monthText:"เดือน",secText:"วินาที",timeFormat:"HH:ii",yearText:"ปี",nowText:"ตอนนี้",pmText:"pm",amText:"am",firstDay:0,dateText:"วัน",timeText:"เวลา",today:"วันนี้",prevMonthText:"เดือนก่อนหน้า",nextMonthText:"เดือนถัดไป",prevYearText:"ปีก่อนหน้า",nextYearText:"ปีถัดไป",closeText:"ปิด",eventText:"เหตุการณ์",eventsText:"เหตุการณ์",allDayText:"ตลอดวัน",noEventsText:"ไม่มีกิจกรรม",moreEventsText:"อีก {count} กิจกรรม",fromText:"จาก",toText:"ถึง",wholeText:"ทั้งหมด",fractionText:"เศษส่วน",unitText:"หน่วย",labels:["ปี","เดือน","วัน","ชั่วโมง","นาที","วินาที",""],labelsShort:["ปี","เดือน","วัน","ชั่วโมง","นาที","วินาที",""],startText:"เริ่ม",stopText:"หยุด",resetText:"รีเซ็ต",lapText:"รอบ",hideText:"ซ่อน",backText:"ย้อนกลับ",undoText:"เลิกทา",offText:"ปิด",onText:"เปิด",decimalSeparator:",",thousandsSeparator:" "},b.i18n.tr={setText:"Seç",cancelText:"İptal",clearText:"Temizleyin",selectedText:"{count} seçilmiş",dateFormat:"dd.mm.yy",dayNames:["Pazar","Pazartesi","Salı","Çarşamba","Perşembe","Cuma","Cumartesi"],dayNamesShort:["Paz","Pzt","Sal","Çar","Per","Cum","Cmt"],dayNamesMin:["P","P","S","Ç","P","C","C"],dayText:"Gün",delimiter:".",hourText:"Saat",minuteText:"Dakika",monthNames:["Ocak","Şubat","Mart","Nisan","Mayıs","Haziran","Temmuz","Ağustos","Eylül","Ekim","Kasım","Aralık"],monthNamesShort:["Oca","Şub","Mar","Nis","May","Haz","Tem","Ağu","Eyl","Eki","Kas","Ara"],monthText:"Ay",secText:"Saniye",timeFormat:"HH:ii",yearText:"Yıl",nowText:"Şimdi",pmText:"pm",amText:"am",todayText:"Bugün",firstDay:1,dateText:"Tarih",timeText:"Zaman",closeText:"Kapatmak",allDayText:"Tüm gün",noEventsText:"Etkinlik Yok",eventText:"Etkinlik",eventsText:"Etkinlikler",moreEventsText:"{count} tane daha",fromText:"Başla",toText:"Son",wholeText:"Tam",fractionText:"Kesir",unitText:"Birim",labels:["Yıl","Ay","Gün","Saat","Dakika","Saniye",""],labelsShort:["Yıl","Ay","Gün","Sa","Dak","Sn",""],startText:"Başla",stopText:"Durdur",resetText:"Sıfırla",lapText:"Tur",hideText:"Gizle",backText:"Geri",undoText:"Geri Al",offText:"O",onText:"I",decimalSeparator:",",thousandsSeparator:"."},b.i18n.ua={setText:"встановити",cancelText:"відміна",clearText:"очистити",selectedText:"{count} вибрані",dateFormat:"dd.mm.yy",dayNames:["неділя","понеділок","вівторок","середа","четвер","п’ятниця","субота"],dayNamesShort:["нед","пнд","вів","срд","чтв","птн","сбт"],dayNamesMin:["Нд","Пн","Вт","Ср","Чт","Пт","Сб"],dayText:"День",delimiter:".",hourText:"година",minuteText:"хвилина",monthNames:["Січень","Лютий","Березень","Квітень","Травень","Червень","Липень","Серпень","Вересень","Жовтень","Листопад","Грудень"],monthNamesShort:["Січ","Лют","Бер","Кві","Тра","Чер","Лип","Сер","Вер","Жов","Лис","Гру"],monthText:"Місяць",secText:"Секунд",timeFormat:"H:ii",yearText:"Рік",nowText:"Зараз",pmText:"pm",amText:"am",firstDay:1,dateText:"дата",timeText:"Час",todayText:"Сьогодні",prevMonthText:"Попередній місяць",nextMonthText:"Наступного місяця",prevYearText:"Попередній рік",nextYearText:"Наступного року",closeText:"Закрити",eventText:"подія",eventsText:"події",allDayText:"Увесь день",noEventsText:"Жодної події",moreEventsText:"та ще {count}",fromText:"від",toText:"кінець",wholeText:"всі",fractionText:"фракція",unitText:"одиниця",labels:["Рік","Місяць","День","година","хвилина","Секунд",""],labelsShort:["Рік","Місяць","День","година","хвилина","Секунд",""],startText:"Початок",stopText:"СТОП",resetText:"скинути",lapText:"коло",hideText:"сховати",backText:"назад",undoText:"відмінити",offText:"Вимикати",onText:"Увімкнути",decimalSeparator:",",thousandsSeparator:" "},b.i18n.vi={setText:"Đặt",cancelText:"Hủy bò",clearText:"Xóa",selectedText:"{count} chọn",dateFormat:"dd/mm/yy",dayNames:["Chủ Nhật","Thứ Hai","Thứ Ba","Thứ Tư","Thứ Năm","Thứ Sáu","Thứ Bảy"],dayNamesShort:["CN","T2","T3","T4","T5","T6","T7"],dayNamesMin:["CN","T2","T3","T4","T5","T6","T7"],dayText:"",delimiter:"/",hourText:"Giờ",minuteText:"Phút",monthNames:["Tháng Một","Tháng Hai","Tháng Ba","Tháng Tư","Tháng Năm","Tháng Sáu","Tháng Bảy","Tháng Tám","Tháng Chín","Tháng Mười","Tháng Mười Một","Tháng Mười Hai"],monthNamesShort:["Tháng 1","Tháng 2","Tháng 3","Tháng 4","Tháng 5","Tháng 6","Tháng 7","Tháng 8","Tháng 9","Tháng 10","Tháng 11","Tháng 12"],monthText:"Tháng",secText:"Giây",timeFormat:"H:ii",yearText:"Năm",nowText:"Bây giờ",pmText:"pm",amText:"am",firstDay:0,dateText:"Ngày",timeText:"Hồi",todayText:"Hôm nay",prevMonthText:"Tháng trước",nextMonthText:"Tháng tới",prevYearText:"Măm trước",nextYearText:"Năm tới",closeText:"Đóng",eventText:"Sự kiện",eventsText:"Sự kiện",allDayText:"Cả ngày",noEventsText:"Không có sự kiện",moreEventsText:"{count} thẻ khác",fromText:"Từ",toText:"Tới",wholeText:"Toàn thể",fractionText:"Phân số",unitText:"đơn vị",labels:["Năm","Tháng","Ngày","Giờ","Phút","Giây",""],labelsShort:["Năm","Tháng","Ngày","Giờ","Phút","Giây",""],startText:"Bắt đầu",stopText:"Dừng",resetText:"Đặt lại",lapText:"Vòng",hideText:"Giấu",backText:"Quay lại",undoText:"Hoàn tác",offText:"Tất",onText:"Bật",decimalSeparator:",",thousandsSeparator:" "},b.i18n.zh={setText:"确定",cancelText:"取消",clearText:"明确",selectedText:"{count} 选",dateFormat:"yy年mm月d日",dayNames:["周日","周一","周二","周三","周四","周五","周六"],dayNamesShort:["日","一","二","三","四","五","六"],dayNamesMin:["日","一","二","三","四","五","六"],dayText:"日",hourText:"时",minuteText:"分",monthNames:["1月","2月","3月","4月","5月","6月","7月","8月","9月","10月","11月","12月"],monthNamesShort:["一","二","三","四","五","六","七","八","九","十","十一","十二"],monthText:"月",secText:"秒",timeFormat:"HH:ii",yearText:"年",nowText:"当前",pmText:"下午",amText:"上午",yearSuffix:"年",monthSuffix:"月",daySuffix:"日",todayText:"今天",dateText:"日",timeText:"时间",closeText:"关闭",allDayText:"全天",noEventsText:"无事件",eventText:"活动",eventsText:"活动",moreEventsText:"他 {count} 件",fromText:"开始时间",toText:"结束时间",wholeText:"合计",fractionText:"分数",unitText:"单位",labels:["年","月","日","小时","分钟","秒",""],labelsShort:["年","月","日","点","分","秒",""],startText:"开始",stopText:"停止",resetText:"重置",lapText:"圈",hideText:"隐藏",backText:"返回",undoText:"复原",offText:"关闭",onText:"开启",decimalSeparator:",",thousandsSeparator:" "},ai=b.themes,ai.frame.bootstrap={disabledClass:"disabled",selectedClass:"btn-primary",selectedTabClass:"active",tabLink:!0,todayClass:"text-primary mbsc-cal-today",onMarkupInserted:function(d){var b=a(d.target),c=a(".mbsc-cal-tabs",b);a(".mbsc-fr-popup",b).addClass("popover"),a(".mbsc-fr-w",b).addClass("popover-content"),a(".mbsc-fr-hdr",b).addClass("popover-title popover-header"),a(".mbsc-fr-arr-i",b).addClass("popover"),a(".mbsc-fr-arr",b).addClass("arrow"),a(".mbsc-fr-btn",b).addClass("btn btn-default btn-secondary"),a(".mbsc-fr-btn-s .mbsc-fr-btn",b).removeClass("btn-default btn-secondary").addClass("btn btn-primary"),c.addClass("nav nav-tabs"),c.find(".mbsc-cal-tab").addClass("nav-item"),c.find("a").addClass("nav-link"),c.find(".mbsc-cal-tab.active .nav-link").addClass("active"),a(".mbsc-cal-picker",b).addClass("popover"),a(".mbsc-range-btn",b).addClass("btn btn-sm btn-small btn-default"),a(".mbsc-np-btn",b).addClass("btn btn-default"),a(".mbsc-sel-filter-cont",b).removeClass("mbsc-input"),a(".mbsc-sel-filter-input",b).addClass("form-control")},onTabChange:function(c,b){a(".mbsc-cal-tabs .nav-link",b._markup).removeClass("active"),a(".mbsc-cal-tab.active .nav-link",b._markup).addClass("active")},onPosition:function(b){setTimeout(function(){a(".mbsc-fr-bubble-top, .mbsc-fr-bubble-top .mbsc-fr-arr-i",b.target).removeClass("bottom bs-popover-bottom").addClass("top bs-popover-top"),a(".mbsc-fr-bubble-bottom, .mbsc-fr-bubble-bottom .mbsc-fr-arr-i",b.target).removeClass("top bs-popover-top").addClass("bottom  bs-popover-bottom")},10)}},ai.scroller.bootstrap=c({},ai.frame.bootstrap,{dateDisplay:"Mddyy",btnCalPrevClass:"mbsc-ic mbsc-ic-arrow-left5",btnCalNextClass:"mbsc-ic mbsc-ic-arrow-right5",btnPlusClass:"mbsc-ic mbsc-ic-arrow-down5 btn-light",btnMinusClass:"mbsc-ic mbsc-ic-arrow-up5 btn-light",selectedLineHeight:!0,onEventBubbleShow:function(c){var b=a(c.eventList);a(".mbsc-cal-event-list",b).addClass("list-group"),a(".mbsc-cal-event",b).addClass("list-group-item")}}),ai.navigation.bootstrap={wrapperClass:"popover panel panel-default",groupClass:"btn-group",activeClass:"btn-primary",disabledClass:"disabled",itemClass:"btn btn-default"},ai.form.bootstrap={},ah=b.themes;function bw(b,f){var g=e(f,"X",!0),j=e(f,"Y",!0),d=b.offset(),h=g-d.left,i=j-d.top,k=Math.max(h,b[0].offsetWidth-h),l=Math.max(i,b[0].offsetHeight-i),c=2*Math.sqrt(Math.pow(k,2)+Math.pow(l,2));aG(L),L=a('<span class="mbsc-ripple"></span>').css({width:c,height:c,top:j-d.top-c/2,left:g-d.left-c/2}).appendTo(b),setTimeout(function(){L.addClass("mbsc-ripple-scaled mbsc-ripple-visible")},10)}function aG(a){setTimeout(function(){a&&(a.removeClass("mbsc-ripple-visible"),setTimeout(function(){a.remove()},2e3))},100)}function cr(f,b,g,h){var c,d;f.off(".mbsc-ripple").on("touchstart.mbsc-ripple mousedown.mbsc-ripple",b,function(b){N(b,this)&&(c=e(b,"X"),d=e(b,"Y"),(G=a(this)).hasClass(g)||G.hasClass(h)?G=null:bw(G,b))}).on("touchmove.mbsc-ripple mousemove.mbsc-ripple",b,function(a){(G&&9<Math.abs(e(a,"X")-c)||9<Math.abs(e(a,"Y")-d))&&(aG(L),G=null)}).on("touchend.mbsc-ripple touchcancel.mbsc-ripple mouseleave.mbsc-ripple mouseup.mbsc-ripple",b,function(){G&&(setTimeout(function(){aG(L)},100),G=null)})}return ah.frame.ios={display:"bottom",headerText:!1,btnWidth:!1,deleteIcon:"ios-backspace",scroll3d:"wp"!=m&&("android"!=m||7<x)},ah.scroller.ios=c({},ah.frame.ios,{rows:5,height:34,minWidth:55,selectedLineHeight:!0,selectedLineBorder:1,showLabel:!1,useShortLabels:!0,btnPlusClass:"mbsc-ic mbsc-ic-arrow-down5",btnMinusClass:"mbsc-ic mbsc-ic-arrow-up5",checkIcon:"ion-ios7-checkmark-empty",filterClearIcon:"ion-close-circled",dateDisplay:"MMdyy",btnCalPrevClass:"mbsc-ic mbsc-ic-arrow-left5",btnCalNextClass:"mbsc-ic mbsc-ic-arrow-right5"}),ah.listview.ios={leftArrowClass:"mbsc-ic-ion-ios7-arrow-back",rightArrowClass:"mbsc-ic-ion-ios7-arrow-forward"},ah.form.ios={},aa=b.themes,aa.frame.material={headerText:!1,btnWidth:!1,deleteIcon:"material-backspace",onMarkupReady:function(b){cr(a(b.target),".mbsc-fr-btn-e","mbsc-disabled","mbsc-fr-btn-nhl")}},aa.scroller.material=c({},aa.frame.material,{showLabel:!1,selectedLineBorder:2,weekDays:"min",icon:{filled:"material-star",empty:"material-star-outline"},checkIcon:"material-check",btnPlusClass:"mbsc-ic mbsc-ic-material-keyboard-arrow-down",btnMinusClass:"mbsc-ic mbsc-ic-material-keyboard-arrow-up",btnCalPrevClass:"mbsc-ic mbsc-ic-material-keyboard-arrow-left",btnCalNextClass:"mbsc-ic mbsc-ic-material-keyboard-arrow-right"}),aa.listview.material={leftArrowClass:"mbsc-ic-material-keyboard-arrow-left",rightArrowClass:"mbsc-ic-material-keyboard-arrow-right",onItemActivate:function(b){bw(a(b.target),b.domEvent)},onItemDeactivate:function(){aG(L)},onSlideStart:function(b){a(".mbsc-ripple",b.target).remove()},onSortStart:function(b){a(".mbsc-ripple",b.target).remove()}},aa.navigation.material={onInit:function(){cr(a(this),".mbsc-ms-item.mbsc-btn-e","mbsc-disabled","mbsc-btn-nhl")},onMarkupInit:function(){a(".mbsc-ripple",this).remove()},onDestroy:function(){a(this).off(".mbsc-ripple")}},aa.form.material={addRipple:function(a,b){bw(a,b)},removeRipple:function(){aG(L)}},aH=b.themes,aH.frame.windows={headerText:!1,deleteIcon:"backspace4",btnReverse:!0},aH.scroller.windows=c({},aH.frame.windows,{rows:6,minWidth:88,height:44,btnPlusClass:"mbsc-ic mbsc-ic-arrow-down5",btnMinusClass:"mbsc-ic mbsc-ic-arrow-up5",checkIcon:"material-check",dateDisplay:"MMdyy",showLabel:!1,showScrollArrows:!0,btnCalPrevClass:"mbsc-ic mbsc-ic-arrow-left5",btnCalNextClass:"mbsc-ic mbsc-ic-arrow-right5",dayNamesShort:["Su","Mo","Tu","We","Th","Fr","Sa"],useShortLabels:!0}),aH.form.windows={},b.customTheme("ios-dark","ios"),b.customTheme("material-dark","material"),b.customTheme("mobiscroll-dark","mobiscroll"),b.customTheme("windows-dark","windows"),dn=b.themes,R="mobiscroll","android"==m?R="material":"ios"==m?R="ios":"wp"==m&&(R="windows"),a.each(dn.frame,function(a,c){if(R&&c.baseTheme==R&&a!=R+"-dark")return b.autoTheme=a,!1;a==R&&(b.autoTheme=a)}),b.customTheme("ios-gray","ios"),b.customTheme("material-indigo","material"),b.customTheme("mobiscroll-lime","mobiscroll"),b.customTheme("windows-yellow","windows"),b})