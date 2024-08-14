import { createApp, reactive } from 'https://unpkg.com/petite-vue?module';

const App = reactive({
  loaded: false,

  init() {
    this.loaded = true;
    Hide();
  }
});

const ClearableInput = (props) => {
  return {
    name: props.name,
    value: props.value,
    errors: props.errors,
    showButton: false,
    input() {
      this.showButton = this.value !== '';
      this.errors = [];
    },
    focus() {
      this.showButton = this.value !== '';
    },
    blur($el) {
      const button = $el.closest('div').querySelector('button');
      this.showButton = (button === document.activeElement) && (this.value !== '');
    },
    clear($el) {
      this.value = '';
      this.errors = [];
      const input = $el.closest('div').querySelector('input.js-clearable, textarea.js-clearable');
      input.focus();
    },
  }
};

const LineItem = (props) => {
  return {
    id: props.lineItemId,
    qty: props.qty,
    min: props.min,
    max: props.max,
    input() {
      this.qty = this.qty.replace(/\D/g,'');
      this.updateQty();
    },
    increment() {
      this.qty++;
      this.updateQty();
    },
    decrement() {
      this.qty = this.qty > 0 ? (this.qty - 1) : 0;
      this.updateQty();
    },
    remove() {
      this.qty = 0;
      this.updateQty();
    },
    blur() {
      this.qty = this.qty === '' ? 0 : this.qty;
    },
    updateQty() {
      props.qty = this.qty;
      if(props.qty == 0) {
        RemoveLineItem(props);
      } else {
        UpdateQty(props);
      }
    }
  }
};

const UpdateQty =  (props) => {
  const form = document.querySelector(`#lineItemQty-${props.id}`);
  const formData = new FormData(form)
  formData.set(`lineItems[${props.id}][qty]`, props.qty);
  UpdateCart(formData);
}

const RemoveLineItem = (props) => {
  const form = document.querySelector(`#lineItemQty-${props.id}`);
  const formData = new FormData(form)
  formData.set(`lineItems[${props.id}][remove]`, true);
  UpdateCart(formData);
  // we need to only do this if the ajax operation was successful
  //  const container = form.closest('article');
  //  container.remove();
}


const UpdateCart = async (formData) => {
  await fetch('/actions/commerce/cart/update-cart', {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: formData,
  })
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .catch(error => {
      console.error('Error:', error);
    });
}

const FocusModal = (props) => {
  return {
    isOpen: props.isOpen,
    toggleModal() {
      this.isOpen = !this.isOpen;
    },
    mounted() {
      const modal = this.$refs.modal;
      const focusableEls = modal.querySelectorAll('a[href]:not([disabled]), button:not([disabled]), textarea:not([disabled]), input[type="text"]:not([disabled]), input[type="radio"]:not([disabled]), input[type="checkbox"]:not([disabled]), select:not([disabled])');
      const firstFocusableEl = focusableEls[0];
      const lastFocusableEl = focusableEls[focusableEls.length - 1];
      const KEYCODE_TAB = 9;

      modal.addEventListener('keydown', (evt) => {
        const isTab = (evt.key === 'Tab' || evt.keyCode === KEYCODE_TAB);
        if (isTab) {
          if (evt.shiftKey) {
            if (document.activeElement === firstFocusableEl) {
              lastFocusableEl.focus();
              evt.preventDefault();
            }
          } else {
            if (document.activeElement === lastFocusableEl) {
              firstFocusableEl.focus();
              evt.preventDefault();
            }
          }
        }
      });
    }
  }
};

// Hide any elements that we only show if JS doesn't load
const Hide = () => {
    const els = document.querySelectorAll('.js-hide')
    els.forEach((el) => el.classList.add('hidden'));
}

// Initialize Petite Vue and mount
createApp({
  // Set delimiters in Petite Vue so they do not interfere with Twig delimiters
  $delimiters: ['${', '}'],

  // Include the modules above
  App,
  ClearableInput,
  FocusModal,
  LineItem,
  Hide
}).mount();
