import './navbar';
import './modal';
import './admin-ajax';
import './filter';
import './slider';
import './smooth-scroll';
import './section-nav';
import './reveal';
import './product-gallery';
import './contact-form';
import './toast';
import './transaction-form';
import './transaction-modal';
import './login';
import './page-transitions';

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-stock-material]');
    if (!trigger) return;
    const select = document.querySelector('#add-material-stock select[name="raw_material_id"]');
    if (select) select.value = trigger.dataset.stockMaterial;
});
