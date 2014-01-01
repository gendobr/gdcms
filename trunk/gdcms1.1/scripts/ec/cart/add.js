/*
 * Add product to cart using "drag and drop" style
 */
Event.observe(window, 'load', initApp, false);
function initApp(){
    new Draggable('ec_item_1',{
        scroll: window,
        revert: true,
        snap: [40, 40]
    });
    new Draggable('ec_item_2',{
        scroll: window,
        revert: true,
        snap: [40, 40]
    });
    new Draggable('ec_item_3',{
        scroll: window,
        revert: true,
        snap: [40, 40]
    });
    new Draggable('ec_item_4',{
        scroll: window,
        revert: true,
        snap: [40, 40]
    });
    new Draggable('ec_item_5',{
        scroll: window,
        revert: true,
        snap: [40, 40]
    });
    Droppables.add('ec_cart', {
        hoverclass: 'hover',
        onDrop: function(dragged, dropped, event) {
            dropped.highlight();
        //alert('Dragged: ' + dragged.id);
        //alert('Dropped onto: ' + dropped.id);
        //alert('Held ctrl key: ' + event.ctrlKey);
        }

    });
// alert('OK');
}


