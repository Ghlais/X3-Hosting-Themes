import {test} from 'node:test';
import assert from 'node:assert/strict';
import {createComparison} from '../themes/x3-hosting/js/comparison.js';
const make=()=>createComparison(['1','2','3','4'].map(id=>({id,name:`Plan ${id}`})),{limit:'limit',need:'need',missing:'missing'});
test('only known products and at most three selections',()=>{
 const c=make(); c.toggle('unknown'); assert.deepEqual(c.selected,[]);
 ['1','2','3','4'].forEach(id=>c.toggle(id)); assert.deepEqual(c.selected,['1','2','3']); assert.equal(c.message,'limit');
 c.toggle('2'); c.toggle('4'); assert.deepEqual(c.selected,['1','3','4']); assert.equal(c.message,'');
});
test('dialog requires two products and clearing resets state',()=>{
 const c=make(); let opened=0; c.$refs={comparison:{showModal(){opened++},close(){opened--}}};
 c.toggle('1'); c.openComparison(); assert.equal(opened,0); assert.equal(c.message,'need');
 c.toggle('2'); c.openComparison(); assert.equal(opened,1); assert.equal(c.items.length,2);
 c.destroy(); assert.equal(opened,0); c.clear(); assert.equal(c.items.length,0); assert.equal(c.message,'');
});
test('each page starts fresh and does not retain another currency or locale',()=>{
 const c=make(); c.toggle('1'); assert.equal(make().selected.length,0);
});
