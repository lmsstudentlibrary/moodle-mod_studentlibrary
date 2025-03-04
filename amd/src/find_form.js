import $ from 'jquery';
// import ModalConstructor from "./modal_constructor";
// import CustomEvents from 'core/custom_interaction_events';

export const init = (
    serverapi
    , kits_select
    , div_select
    , headerContent
    , search_button
    , add_button
    , search_bar
    , add_button_kit_string
    , lang
    , id_error_booke_not_faind_s
    , id_error_booke_s
    , placeholder_s
) => {
    $('#search_button').on("click", function () {
        // Удаляем артефакты
        let dialogDom = document.getElementsByClassName('search-book-dialogue');
        for (let i = 0; i < dialogDom.length; i++) {
            if (dialogDom[i].getAttribute('aria-hidden') == 'true') {
                dialogDom[i].remove();
            }
        }
        const ssr = this.getAttribute("ssr_p");
        const pagination = 6;
        let content;
        content = Y.Node.create(
            '<div class = "book-dialog" id = "book-dialog">' +
            '<div class="book-search-form" id="book-search-form"><div class="book_search_form_filter" id="book_search_form_filter"></div></div>' +
            '<hr />' +
            '<div class="book-search-list" id="book-search-list"></div>' +
            '<div class="book-pagination" id="book-pagination"></div>' +
            '<hr />' +
            '<div class="book_button" id="book_button" align="center"></div>' +
            '</div>'
        );
        let search_form = content.one('#book-search-form');
        let search_form_filter = content.one('#book_search_form_filter');
        let book_button = content.one('#book_button');
        /**
        * kitsSelect
        */
        let kitsSelect = Y.Node.create('<div><p>' + kits_select + '</p><select id="kits_select" class="kits_select custom-select"></select></div>');
        let kitsSelectOne = kitsSelect.one('#kits_select');
        const kitsURL = serverapi + "db?SSr=" + ssr + "&guide=sengine&cmd=sel&tag=all_agreement_kits";
        let kitsHttp = new XMLHttpRequest();
        kitsHttp.open("GET", kitsURL, false); // false for synchronous request
        kitsHttp.send(null);
        const kitsXmlDoc = convertStringToXML(kitsHttp.responseText);
        const kitsArray = kitsXmlDoc.getElementsByTagName('kit');
        let divSelect = Y.Node.create('<div><p>' + div_select + '</p><select id="div_select" class="div_select custom-select"></select></div>');
        let divSelectOne = divSelect.one('#div_select');
        for (let i = 0; i < kitsArray.length; i++) {
            let option = document.createElement("option");
            option.value = kitsArray[i].getAttribute('id');
            let KitDataList = GetKitData(serverapi, ssr, kitsArray[i].getAttribute('id'));
            const divArray = KitDataList.getElementsByTagName('division');
            if (i == 0) {
                for (let j = 0; j < divArray.length; j++) {
                    let option = document.createElement("option");
                    option.value = divArray[j].getAttribute('id');
                    let divData = GetDivFullName(serverapi, ssr, divArray[j].getAttribute('id'), lang);
                    option.text = divData;
                    divSelectOne.appendChild(option);
                }
            }
            option.text = KitDataList.getElementsByTagName('name')[0].getElementsByTagName('string')[0].innerHTML;
            kitsSelectOne.appendChild(option);
        }
        search_form_filter.appendChild(kitsSelect);
        search_form_filter.appendChild(divSelect);
        let add_button_kit = add_button_kit_string;
        let addkit = Y.Node.create('<button class="btn btn-primary addbook" name="submitbutton_kit" id="id_submitbutton_lit">' + add_button_kit + '</button>');
        addkit.on('click', function () {
            const kits_select = document.getElementById('kits_select');
            if (kits_select) {
                document.getElementById('id_booke').value = 'switch_kit/' + kits_select.value;
                dialogRef.dialog.destroy();
            } else {
                window.console.log('no book selected');
            }
        });
        search_form_filter.appendChild(addkit);
        let input_book_search = Y.Node.create(
            '<div><p>' + search_bar + '</p><input type="text" id="input_book_search" class="input_book_search custom-select" placeholder="' + placeholder_s + '"></div>'
        );
        search_form.appendChild(input_book_search);
        let button_book_search = Y.Node.create('<button type="button" id="button_book_search" class="button_book_search btn btn-outline-primary">' + search_button + '</button>');
        button_book_search.on('click', function () {
            let search_input = document.getElementById('input_book_search').value;
            const div_select = document.getElementById('div_select').value;
            let book_search_list = document.getElementById("book-search-list");
            book_search_list.innerHTML = '';
            const searchURL = serverapi + 'db?SSr=' + ssr + '&cmd=sel&guide=sengine&tag=kwords_search&paginate=' + pagination + '&div=' + div_select + '&kwords="' + search_input + '"';
            let xmlHttp = new XMLHttpRequest();
            xmlHttp.open("GET", searchURL, false); // false for synchronous request
            xmlHttp.send(null);
            BuildBooksList(serverapi, ssr, xmlHttp.responseText, pagination, serverapi);
        });
        let addbook = Y.Node.create('<button class="btn btn-primary addbook" name="submitbutton_book" id="id_submitbutton_book">' + add_button + '</button>');
        addbook.on('click', function () {
            const BookData = document.querySelector('input[name=radio-card-input]:checked');
            if (BookData) {
                document.getElementById('id_booke').value = BookData.value;
                dialogRef.dialog.destroy();
            } else {
                window.console.log('no book selected');
            }
        });
        book_button.appendChild(addbook);
        kitsSelectOne.on('change', function () {
            const selectKit = document.getElementById('kits_select').value;
            let divSelectOne = document.getElementById('div_select');
            divSelectOne.innerHTML = '';
            let DivList = GetKitData(serverapi, ssr, selectKit);
            const divArray = DivList.getElementsByTagName('division');
            for (let j = 0; j < divArray.length; j++) {
                let option = document.createElement("option");
                option.value = divArray[j].getAttribute('id');
                let divData = GetDivFullName(serverapi, ssr, divArray[j].getAttribute('id'), lang);
                option.text = divData;
                divSelectOne.appendChild(option);
            }
        });
        search_form.appendChild(button_book_search);
        let dialogRef = { dialog: null };
        let config = {
            headerContent: headerContent,
            bodyContent: content,
            additionalBaseClass: 'search-book-dialogue',
            draggable: true,
            modal: true,
            closeButton: true,
            width: '50%'
        };
        dialogRef.dialog = new M.core.dialogue(config);
        dialogRef.dialog.show();
    });
    $('#id_booke').on("change", function () {
        const ssr = this.getAttribute("ssr_o");
        const id_error_booke = document.getElementById("id_error_booke");
        id_error_booke.innerHTML = '';
        id_error_booke.style.display = "none";
        let book_id_text = this.value;
        if (book_id_text.indexOf('/doc/') >= 0) {
            const BookID = FainfBookId(book_id_text, book_id_text.indexOf('/doc/') + 1, book_id_text.indexOf('.html'));
            this.value = BookID;
            if (GetBookData(serverapi, ssr, BookID)) {
                window.console.log('book_id ok');
            } else {
                window.console.log('book_id no');
                id_error_booke.innerHTML = id_error_booke_not_faind_s;
                id_error_booke.style.display = "block";
            }
        } else if (book_id_text.indexOf('/book/') >= 0) {
            const BookID = FainfBookId(book_id_text, book_id_text.indexOf('/book/') + 1, book_id_text.indexOf('.html'));
            this.value = BookID;
            if (GetBookData(serverapi, ssr, BookID)) {
                window.console.log('book_id ok');
            } else {
                window.console.log('book_id no');
                id_error_booke.innerHTML = id_error_booke_not_faind_s;
                id_error_booke.style.display = "block";
            }
        } else if (book_id_text.indexOf('doc/') === 0) {
            const BookID = FainfBookId(book_id_text, 0, book_id_text.indexOf('.html'));
            this.value = BookID;
            if (GetBookData(serverapi, ssr, BookID)) {
                window.console.log('book_id ok');
            } else {
                window.console.log('book_id no');
                id_error_booke.innerHTML = id_error_booke_not_faind_s;
                id_error_booke.style.display = "block";
            }
        } else if (book_id_text.indexOf('book/') === 0) {
            const BookID = FainfBookId(book_id_text, 0, book_id_text.indexOf('.html'));
            this.value = BookID;
            if (GetBookData(serverapi, ssr, BookID)) {
                window.console.log('book_id ok');
            } else {
                window.console.log('book_id no');
                id_error_booke.innerHTML = id_error_booke_not_faind_s;
                id_error_booke.style.display = "block";
            }
        } else {
            id_error_booke.innerHTML = id_error_booke_s;
            id_error_booke.style.display = "block";
        }
    });
};

const FainfBookId = function (str, text_s, test_f) {
    let new_text_s = text_s;
    let new_text_f = test_f;
    if (test_f === -1) {
        new_text_f = str.length;
    }
    return (str.substring(new_text_s, new_text_f));
};

const cardclick = (book_id) => {
    unclickRadio();
    removeActive();
    makeActive(book_id);
    clickRadio(book_id);
};

const unclickRadio = () => {
    $("input:radio").prop("checked", false);
};

const clickRadio = (inputElement) => {
    $("#" + inputElement).prop("checked", true);
};

const removeActive = () => {
    $(".card").removeClass("active");
};
const makeActive = (element) => {
    $("#" + element + "-card").addClass("active");
};

const convertStringToXML = (xmlString) => {
    const parser = new DOMParser();
    const xmlDoc = parser.parseFromString(xmlString, "text/xml");
    return xmlDoc;
};

const GetKitData = (server, ssr, selectKit) => {
    const divURL = server + 'db?SSr=' + ssr + '&guide=sengine&cmd=sel&tag=kit_content&kit=' + selectKit;
    let xmlHttp = new XMLHttpRequest();
    xmlHttp.open("GET", divURL, false); // false for synchronous request
    xmlHttp.send(null);
    return (convertStringToXML(xmlHttp.responseText));
};

const GetDivFullName = (server, ssr, divID, page = 0, leng) => {
    let divDataURL = server + 'db?SSr=' + ssr + '&guide=sengine&cmd=sel&tag=division_books&div=' + divID;
    if (page !== 0) {
        divDataURL = divDataURL + '&paginate=' + page;
    } else {
        divDataURL = divDataURL + '&paginate=1';
    }
    let xmlHttp = new XMLHttpRequest();
    xmlHttp.open("GET", divDataURL, false); // false for synchronous request
    xmlHttp.send(null);
    const divData = convertStringToXML(xmlHttp.responseText);
    const full_name_list = divData.getElementsByTagName('full_name');
    if (full_name_list.length > 0) {
        const divName_stringlist = full_name_list[0].querySelector(`[language="${leng}"]`);
        if (divName_stringlist !== null) {
            return divName_stringlist.innerHTML;
        } else {
            return full_name_list[0].getElementsByTagName('string')[0].innerHTML;
        }
    } else {
        return divID;
    }
};

const BuildBooksList = (server, ssr, xmlString, pagination, serverapi, book_total = -1, res_id = -1) => {
    let book_search_list = document.getElementById("book-search-list");
    let book_pagination = document.getElementById("book-pagination");
    book_search_list.innerHTML = '';
    book_pagination.innerHTML = '';
    let xmlDoc = convertStringToXML(xmlString);
    let bookList = xmlDoc.getElementsByTagName('data');
    if (res_id === -1) {
        res_id = xmlDoc.getElementsByTagName('res_id')[0].innerHTML;
    }
    if (book_total === -1) {
        book_total = xmlDoc.getElementsByTagName('total')[0].innerHTML;
    }
    let page = document.getElementById('book-pagination').getAttribute('page');
    if (page === null) {
        page = 1;
    }
    if (bookList.length > 0) {
        for (let i = 0; i < bookList.length; i++) {
            let BookID = bookList[i].innerHTML;
            if (BookID.split('/')[0] === 'book') {
                let Book = BuildBook(server, ssr, BookID);
                book_search_list.appendChild(Book);
            } else if (BookID.split('/')[0] === 'doc') {
                let Book = BuildDoc(server, ssr, BookID);
                book_search_list.appendChild(Book);
            }
        }
        const navBar = BuildBookPagination(res_id, ssr, pagination, book_total, page, serverapi);
        book_pagination.appendChild(navBar);
    } else {
        const notFaund_div = document.createElement('div');
        const notFaund_p = document.createElement('p');
        notFaund_p.innerText = 'Not Found';
        notFaund_div.appendChild(notFaund_p);
        book_search_list.appendChild(notFaund_div);
    }
};

const BuildBook = (server, ssr, BookID) => {
    let GetBookDataURL = server + 'db?SSr=' + ssr + '&guide=' + BookID.split('/')[0] + '&cmd=data&id=' + BookID.split('/')[1] + '&img_src_form=b64';
    let xmlHttp = new XMLHttpRequest();
    xmlHttp.open("GET", GetBookDataURL, false); // false for synchronous request
    xmlHttp.send(null);
    let BookData = convertStringToXML(xmlHttp.responseText);
    let meta = BookData.getElementsByTagName('meta')[0];
    // Layout
    const book_list_item = '<label class="radio-card"><input type="radio" name="radio-card-input" class="radio-card-input"/><div class="card-content-wrapper"><span class="check-icon"></span><div class="card-content"><img></img><div class="metadata"><div class="title"></div><div class="authors"></div><div class="doc_name"></div></div></div></div></label>';
    let div_book_list_item = document.createElement('div');
    div_book_list_item.innerHTML = book_list_item;
    // END_Layout
    let radio_card = div_book_list_item.getElementsByClassName('radio-card')[0];
    radio_card.setAttribute('for', BookID.split('/')[1]);
    div_book_list_item.getElementsByClassName('radio-card-input')[0].setAttribute('id', BookID.split('/')[1]);
    // Name
    if (BookData.getElementsByTagName('title')[0].getElementsByTagName('string').length > 0) {
        div_book_list_item.getElementsByClassName('title')[0].innerHTML = ReplaceCDATA(BookData.getElementsByTagName('title')[0].getElementsByTagName('string')[0].innerHTML);
    }
    // Autor
    if (meta.querySelectorAll('[name="authors"]')[0].getElementsByTagName('string').length > 0) {
        div_book_list_item.getElementsByClassName('authors')[0].innerHTML = ReplaceCDATA(meta.querySelectorAll('[name="authors"]')[0].getElementsByTagName('string')[0].innerHTML);
    }
    // Select book
    div_book_list_item.getElementsByClassName('radio-card-input')[0].value = BookID;
    div_book_list_item.getElementsByClassName("radio-card")[0].onclick = function () {
        cardclick(BookID.split('/')[1]);
    };
    div_book_list_item.getElementsByTagName('img')[0].setAttribute('src', BookData.getElementById("avatar").getAttribute("src"));
    return div_book_list_item;
};

const BuildDoc = (server, ssr, BookID) => {
    let NewBookID = GetBookIdbyDocId(server, ssr, BookID);
    if (NewBookID !== null) {
        let GetBookDataURL = server + 'db?SSr=' + ssr + '&guide=book&cmd=data&id=' + NewBookID + '&img_src_form=b64';
        let xmlHttp = new XMLHttpRequest();
        xmlHttp.open("GET", GetBookDataURL, false); // false for synchronous request
        xmlHttp.send(null);
        let BookData = convertStringToXML(xmlHttp.responseText);
        if (BookData.getElementsByTagName('meta').length > 0) {
            let meta = BookData.getElementsByTagName('meta')[0];
            // Layout
            const book_list_item = '<label class="radio-card"><input type="radio" name="radio-card-input" class="radio-card-input"/><div class="card-content-wrapper"><span class="check-icon"></span><div class="card-content"><img></img><div class="metadata"><div class="doc_name"></div><div class="title"></div><div class="authors"></div></div></div></div></label>';
            let div_book_list_item = document.createElement('div');
            div_book_list_item.innerHTML = book_list_item;
            // END_Layout
            let radio_card = div_book_list_item.getElementsByClassName('radio-card')[0];
            radio_card.setAttribute('for', BookID.split('/')[1] + '_' + BookID.split('/')[2]);
            div_book_list_item.getElementsByClassName('radio-card-input')[0].setAttribute('id', BookID.split('/')[1] + '_' + BookID.split('/')[2]);
            // Name
            if (BookData.getElementsByTagName('title')[0].getElementsByTagName('string').length > 0) {
                div_book_list_item.getElementsByClassName('title')[0].innerHTML = ReplaceCDATA(BookData.getElementsByTagName('title')[0].getElementsByTagName('string')[0].innerHTML);
            }
            // Chapter
            if (BookID.split('/')[2]) {
                div_book_list_item.getElementsByClassName('doc_name')[0].innerHTML = ReplaceCDATA(BookData.getElementById(BookID.split('/')[1]).getElementsByTagName('string')[0].innerHTML) + ' стр. ' + BookID.split('/')[2];
            } else {
                div_book_list_item.getElementsByClassName('doc_name')[0].innerHTML = ReplaceCDATA(BookData.getElementById(BookID.split('/')[1]).getElementsByTagName('string')[0].innerHTML);
            }
            // Author
            if (meta.querySelectorAll('[name="authors"]')[0].getElementsByTagName('string').length > 0) {
                div_book_list_item.getElementsByClassName('authors')[0].innerHTML = ReplaceCDATA(meta.querySelectorAll('[name="authors"]')[0].getElementsByTagName('string')[0].innerHTML);
            }
            // Select book
            div_book_list_item.getElementsByClassName('radio-card-input')[0].value = BookID;
            div_book_list_item.getElementsByClassName("radio-card")[0].onclick = function () {
                cardclick(BookID.split('/')[1] + '_' + BookID.split('/')[2]);
            };
            div_book_list_item.getElementsByTagName('img')[0].setAttribute('src', BookData.getElementById("avatar").getAttribute("src"));
            return div_book_list_item;
        } else {
            let div_book_list_item = document.createElement('div');
            return div_book_list_item;
        }
    } else {
        let div_book_list_item = document.createElement('div');
        return div_book_list_item;
    }
};

const ReplaceCDATA = (str) => {
    return (str.replace("<![CDATA[", "").replace("]]>", ""));
};

const PaginationClick = (ssr, res_id, page, pagination, serverapi, total) => {
    let book_search_list = document.getElementById("book-search-list");
    book_search_list.innerHTML = '';
    const searchURL = serverapi + 'db?SSr=' + ssr + '&cmd=more&guide=sengine&res_id=' + res_id + '&from=' + ((page - 1) * pagination);
    let xmlHttp = new XMLHttpRequest();
    xmlHttp.open("GET", searchURL, false); // false for synchronous request
    xmlHttp.send(null);
    document.getElementById('book-pagination').setAttribute('page', page);
    BuildBooksList(serverapi, ssr, xmlHttp.responseText, pagination, serverapi, total, res_id);
};

const BuildBookPagination = (res_id, ssr, pagination, total, page = 1, serverapi) => {
    const pageCount = Math.ceil(total / pagination);
    let nav_bar = document.createElement('div');
    nav_bar.id = 'book_nav_bar';
    nav_bar.classList.add('book_nav_bar');
    nav_bar.classList.add('pagination');
    nav_bar.classList.add('pagination-centered');
    nav_bar.classList.add('justify-content-center');
    let nav_bar_ul = document.createElement('ui');
    nav_bar_ul.classList.add('pagination');
    nav_bar_ul.setAttribute('data-page-size', 20);
    if (pageCount > 1) {
        // the back button
        if (page > 1) {
            let previous_page = document.createElement('li');
            previous_page.classList.add('page-item');
            previous_page.classList.add('page-item-book');
            previous_page.onclick = function () { PaginationClick(ssr, res_id, Number(page) - 1, pagination, serverapi, total); };
            let previous_page_a = document.createElement('a');
            previous_page_a.classList.add('page-link');
            previous_page_a.setAttribute('page', Number(page) - 1);
            previous_page_a.innerHTML = '<span aria-hidden="true">«</span>';
            previous_page.appendChild(previous_page_a);
            nav_bar_ul.appendChild(previous_page);
        }
        // the page 1 button
        let first_page = document.createElement('li');
        first_page.classList.add('page-item');
        first_page.classList.add('page-item-book');
        if (page == 1) {
            first_page.classList.add('active');
        }
        first_page.onclick = function () { PaginationClick(ssr, res_id, 1, pagination, serverapi, total); };
        let first_page_a = document.createElement('a');
        first_page_a.classList.add('page-link');
        first_page_a.setAttribute('page', 1);
        first_page_a.innerHTML = '<span aria-hidden="true">1</span>';
        first_page.appendChild(first_page_a);
        nav_bar_ul.appendChild(first_page);
        // the current one ...
        if (page > 3) {
            let previous_one_page_space = document.createElement('li');
            previous_one_page_space.classList.add('page-item');
            previous_one_page_space.classList.add('disabled');
            previous_one_page_space.classList.add('page-item-book');
            let previous_one_page_space_a = document.createElement('a');
            previous_one_page_space_a.classList.add('page-link');
            previous_one_page_space_a.innerHTML = '<span aria-hidden="true">...</span>';
            previous_one_page_space.appendChild(previous_one_page_space_a);
            nav_bar_ul.appendChild(previous_one_page_space);
        }
        // current page - 1
        if (page > 2) {
            let previous_one_page = document.createElement('li');
            previous_one_page.classList.add('page-item');
            previous_one_page.classList.add('page-item-book');
            previous_one_page.onclick = function () { PaginationClick(ssr, res_id, Number(page) - 1, pagination, serverapi, total); };
            let previous_one_page_a = document.createElement('a');
            previous_one_page_a.classList.add('page-link');
            previous_one_page_a.setAttribute('page', Number(page) - 1);
            previous_one_page_a.innerHTML = '<span aria-hidden="true">' + (Number(page) - 1) + '</span>';
            previous_one_page.appendChild(previous_one_page_a);
            nav_bar_ul.appendChild(previous_one_page);
        }
        // current page
        if (page > 1 && page < (Number(pageCount))) {
            let Current_one_page = document.createElement('li');
            Current_one_page.classList.add('page-item');
            Current_one_page.classList.add('page-item-book');
            Current_one_page.classList.add('active');
            let Current_one_page_a = document.createElement('a');
            Current_one_page_a.classList.add('page-link');
            Current_one_page_a.innerHTML = '<span aria-hidden="true">' + Number(page) + '</span>';
            Current_one_page.appendChild(Current_one_page_a);
            nav_bar_ul.appendChild(Current_one_page);
        }
        // current page + 1
        if (page < (Number(pageCount) - 1)) {
            let next_one_page = document.createElement('li');
            next_one_page.classList.add('page-item');
            next_one_page.classList.add('page-item-book');
            next_one_page.onclick = function () { PaginationClick(ssr, res_id, Number(page) + 1, pagination, serverapi, total); };
            let next_one_page_a = document.createElement('a');
            next_one_page_a.classList.add('page-link');
            next_one_page_a.setAttribute('page', Number(page) + 1);
            next_one_page_a.innerHTML = '<span aria-hidden="true">' + (Number(page) + 1) + '</span>';
            next_one_page.appendChild(next_one_page_a);
            nav_bar_ul.appendChild(next_one_page);
        }
        // current page ...
        if (page < (Number(pageCount) - 2)) {
            let next_one_page_space = document.createElement('li');
            next_one_page_space.classList.add('page-item');
            next_one_page_space.classList.add('disabled');
            next_one_page_space.classList.add('page-item-book');
            let next_one_page_space_a = document.createElement('a');
            next_one_page_space_a.classList.add('page-link');
            next_one_page_space_a.innerHTML = '<span aria-hidden="true">...</span>';
            next_one_page_space.appendChild(next_one_page_space_a);
            nav_bar_ul.appendChild(next_one_page_space);
        }
        // current page last
        let last_page = document.createElement('li');
        last_page.classList.add('page-item');
        last_page.classList.add('page-item-book');
        last_page.onclick = function () { PaginationClick(ssr, res_id, pageCount, pagination, serverapi, total); };
        if (page == pageCount) {
            last_page.classList.add('active');
        }
        let last_page_a = document.createElement('a');
        last_page_a.classList.add('page-link');
        last_page_a.setAttribute('page', pageCount);
        last_page_a.innerHTML = '<span aria-hidden="true">' + pageCount + '</span>';
        last_page.appendChild(last_page_a);
        nav_bar_ul.appendChild(last_page);
        // button next
        if (page < pageCount) {
            let next_page = document.createElement('li');
            next_page.classList.add('page-item');
            next_page.classList.add('page-item-book');
            next_page.onclick = function () { PaginationClick(ssr, res_id, Number(page) + 1, pagination, serverapi, total); };
            let next_page_a = document.createElement('a');
            next_page_a.classList.add('page-link');
            last_page_a.setAttribute('page', Number(page) + 1);
            next_page_a.innerHTML = '<span aria-hidden="true">»</span>';
            next_page.appendChild(next_page_a);
            nav_bar_ul.appendChild(next_page);
        }
    }
    document.getElementById('book-pagination').setAttribute('page', page);
    nav_bar.appendChild(nav_bar_ul);
    return nav_bar;
};

const GetBookIdbyDocId = (server, ssr, BookID) => {
    let master_book_data_URL = server + 'db?SSr=' + ssr + '&guide=doc&cmd=data&id=' + BookID.split('/')[1] + '&tag=master_book_data';
    let xmlHttp = new XMLHttpRequest();
    xmlHttp.open("GET", master_book_data_URL, false); // false for synchronous request
    xmlHttp.send(null);
    let master_book_data = convertStringToXML(xmlHttp.responseText);
    let BookData = master_book_data.getElementsByTagName('book');
    if (BookData.length > 0) {
        return BookData[0].getAttribute('id');
    } else {
        return null;
    }
};

const GetBookData = (server, ssr, BookID) => {
    let GetBookDataURL = server + 'db?SSr=' + ssr + '&guide=' + BookID.split('/')[0] + '&cmd=data&id=' + BookID.split('/')[1] + '&img_src_form=b64';
    let xmlHttp = new XMLHttpRequest();
    xmlHttp.open("GET", GetBookDataURL, false); // false for synchronous request
    xmlHttp.send(null);
    let BookData = convertStringToXML(xmlHttp.responseText);
    let meta = BookData.getElementsByTagName('title');
    if (meta.length > 0) {
        return true;
    } else {
        return false;
    }
};