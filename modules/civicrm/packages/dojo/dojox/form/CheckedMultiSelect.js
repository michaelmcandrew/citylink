/*
	Copyright (c) 2004-2008, The Dojo Foundation
	All Rights Reserved.

	Licensed under the Academic Free License version 2.1 or above OR the
	modified BSD license. For more information on Dojo licensing, see:

		http://dojotoolkit.org/book/dojo-book-0-9/introduction/licensing
*/


if(!dojo._hasResource["dojox.form.CheckedMultiSelect"]){
dojo._hasResource["dojox.form.CheckedMultiSelect"]=true;
dojo.provide("dojox.form.CheckedMultiSelect");
dojo.require("dijit.form.MultiSelect");
dojo.require("dijit.form.CheckBox");
dojo.declare("dojox.form._CheckedMultiSelectItem",[dijit._Widget,dijit._Templated],{widgetsInTemplate:true,templateString:"<div class=\"dijitReset ${baseClass}\"\n\t><input class=\"${baseClass}Box\" dojoType=\"dijit.form.CheckBox\" dojoAttachPoint=\"checkBox\" dojoAttachEvent=\"_onClick:_changeBox\" type=\"checkbox\" \n\t><div class=\"dijitInline ${baseClass}Label\" dojoAttachPoint=\"labelNode\" dojoAttachEvent=\"onmousedown:_onMouse,onmouseover:_onMouse,onmouseout:_onMouse,onclick:_onClick\">${option.innerHTML}</div\n></div>\n",baseClass:"dojoxMultiSelectItem",option:null,parent:null,disabled:false,_changeBox:function(){
this.option.selected=this.checkBox.getValue()&&true;
this.parent._onChange();
this.parent.focus();
},_labelClick:function(){
dojo.stopEvent(e);
if(this.disabled){
return;
}
var cb=this.checkBox;
cb.setValue(!cb.getValue());
this._changeBox();
},_onMouse:function(e){
this.checkBox._onMouse(e);
},_onClick:function(e){
this.checkBox._onClick(e);
},_updateBox:function(){
this.checkBox.setValue(this.option.selected);
},setAttribute:function(_4,_5){
this.inherited(arguments);
switch(_4){
case "disabled":
this.checkBox.setAttribute(_4,_5);
break;
default:
break;
}
}});
dojo.declare("dojox.form.CheckedMultiSelect",dijit.form.MultiSelect,{templateString:"",templateString:"<div class=\"dijit dijitReset dijitInline\" dojoAttachEvent=\"onmousedown:_mouseDown,onclick:focus\"\n\t><select class=\"${baseClass}Select\" multiple=\"true\" dojoAttachPoint=\"containerNode,focusNode\" dojoAttachEvent=\"onchange: _onChange\"></select\n\t><div dojoAttachPoint=\"wrapperDiv\"></div\n></div>\n",baseClass:"dojoxMultiSelect",children:[],options:null,_mouseDown:function(e){
dojo.stopEvent(e);
},_updateChildren:function(){
dojo.forEach(this.children,function(_7){
_7._updateBox();
});
},_addChild:function(_8){
var _9=new dojox.form._CheckedMultiSelectItem({option:_8,parent:this});
this.wrapperDiv.appendChild(_9.domNode);
return _9;
},_loadChildren:function(){
dojo.forEach(this.children,function(_a){
_a.destroyRecursive();
});
this.children=dojo.query("option",this.domNode).map(function(_b){
return this._addChild(_b);
},this);
this.options=dojo.map(this.children,function(_c){
var _d=_c.option;
return {value:_d.value,label:_d.text};
});
this._updateChildren();
},addOption:function(_e,_f){
var o=new Option("","");
o.value=_e.value||_e;
o.innerHTML=_e.label||_f;
this.containerNode.appendChild(o);
},removeOption:function(_11){
dojo.query("option[value="+_11+"]",this.domNode).forEach(function(_12){
_12.parentNode.removeChild(_12);
},this);
},setOptionLabel:function(_13,_14){
dojo.query("option[value="+_13+"]",this.domNode).forEach(function(_15){
_15.innerHTML=_14;
});
},addSelected:function(_16){
this.inherited(arguments);
if(_16._loadChildren){
_16._loadChildren();
}
this._loadChildren();
},setAttribute:function(_17,_18){
this.inherited(arguments);
switch(_17){
case "disabled":
dojo.forEach(this.children,function(_19){
if(_19&&_19.setAttribute){
_19.setAttribute(_17,_18);
}
});
break;
default:
break;
}
},startup:function(){
if(this._started){
return;
}
this.inherited(arguments);
this._loadChildren();
this.connect(this,"setValue","_updateChildren");
this.connect(this,"invertSelection","_updateChildren");
this.connect(this,"addOption","_loadChildren");
this.connect(this,"removeOption","_loadChildren");
this.connect(this,"setOptionLabel","_loadChildren");
this._started=true;
}});
}
