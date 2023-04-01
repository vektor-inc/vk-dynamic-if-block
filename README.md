# vk-dynamic-if-block

WordPress の ブロックエディタ用のブロックのプラグインを作りたいです。
必要なファイル構成とファイルの中身を順に出力してください。

* WordPressのコーディング規約に沿ったフォーマットでよろしくお願いいたします。

## このプラグインの役割

* Dynamic If Block は インナーブロックを持ち、表示中の条件に合致したらそのインナーブロックに設置されたコンテンツを表示します。
* Dynamic If Block の インナーブロックにユーザーは任意のブロックを自由に配置できます。
* 投稿編集画面でこのブロックを選択した時のサイドバーから どの条件に合致した時に表示するかを指定できます。

## プラグインの仕様

プラグイン名 : VK Dynamic If Block
Author : Vektor,Inc.
プラグインの Discription も適当に英語で記載よろしくお願いいたします。

## readme.txt について

WordPress公式ディレクトリに登録したいので readme.txt も生成してください。
以下は含めてください。

Tested up to: 6.2
Requires at least: 6.0
Version 0.1.0

WordPress公式プラグインディレクトリに登録するにあたって足りない記述は適当にダミーで入力してください。

## ブロックの仕様

* ブロックの表示名 Dynamic If
* ブロック名 vk-blocks/dynamic-if

### インナーブロックに配置されたコンテンツの表示条件について

#### 選択肢

制限なし（デフォルト） / if_front_page() / is_single()

* 投稿編集画面での Dynamic If ブロックの設定項目は、プルダウン項目で、 is_front_page() と is_single() が選択できます。
* 配置した Dynamic If ブロックに対して、is_front_page() が指定されていたら、その Dynamic If ブロックのインナーブロックに設置されたコンテンツはトップページでのみ表示されるようにしてください。トップページ以外で表示されてはいけません。
* if_single() が指定されている場合、その Dynamic If ブロックに配置されたインナーブロックのコンテンツは、個別投稿ページ以外では表示されないようにしてください（記事の編集画面では表示されているようにしてください）。
* 非表示にする条件分岐は php の関数の if_front_page() と is_single() を使ってください。
* Dynamic If ブロック php の動的ブロックで処理してください。

### その他

* プラグインとしてすぐ動くように、ディレクトリ直下にプラグイン情報を記載したPHPファイルも生成してください。
* ブロックに関するファイルは src ディレクトリに配置し、ビルドすると build ディレクトリに出力されます
* src/block.json ファイルも必要
* ブロックに関する動的表示処理をする php は src/index.php に記載する
* .gitignore ファイルは不要です。

### CSSについて

* CSSは直接書き込むのではなく、別で src/editor.scss ファイルを用意してそこに記載し、ビルド先は build/editor.css にしてください。
* 投稿編集画面では Dynamic If ブロックは枠線（1px dotted #ccc）が表示されるようにCSSで指定してください。padding は 1px でよろしくお願いいたします。
* build/editor.css は投稿編集画面や彩度エディターでは読み込みますが、公開画面では読み込まないようにしてください。

### package.json についての補足

* package.json も用意してください。
* このプラグインは wp-script を使ってビルドします。
* npx @wordpress/create-block で作られるようなファイル構成、package.json に沿ったものにしてください。
* 開発でのみ必要なパッケージは devDependencies で指定してください。
* package.json には以下も含めてください。
* @wordpress/scripts のバージョンは ^26.1.0 で用意する
* package.json の中に keywords は含めなくてもかまいません。

```
	"author": "Vektor,Inc.",
	"license": "GPL-2.0-or-later",
```

```
	"scripts": {
		"build": "wp-scripts build",
		"format": "wp-scripts format",
		"lint:css": "wp-scripts lint-style",
		"lint:js": "wp-scripts lint-js",
		"packages-update": "wp-scripts packages-update",
		"plugin-zip": "wp-scripts plugin-zip",
		"start": "wp-scripts start"
	},
```

記載の要件は必ず守ってください。
守れない箇所がある場合は該当の項目と、その理由を出力してください。