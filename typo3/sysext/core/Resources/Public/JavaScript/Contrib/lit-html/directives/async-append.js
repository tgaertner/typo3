import{directive as r,PartType as e}from"../directive.js";import{AsyncReplaceDirective as s}from"./async-replace.js";import{clearPart as t,insertPart as o,setChildPartValue as i}from"../directive-helpers.js";
/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const c=r(class extends s{constructor(r){if(super(r),r.type!==e.CHILD)throw Error("asyncAppend can only be used in child expressions")}update(r,e){return this._$Ctt=r,super.update(r,e)}commitValue(r,e){0===e&&t(this._$Ctt);const s=o(this._$Ctt);i(s,r)}});export{c as asyncAppend};
