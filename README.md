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

Tested up to: 6.2
Requires at least: 6.0
Version 0.1.0

## ブロックの仕様

* ブロックの表示名 Dynamic If
* ブロック名 vk-blocks/dynamic-if

### インナーブロックに配置されたコンテンツの表示条件について

#### 選択肢

制限なし（デフォルト） / if_front_page() / is_single()

* 投稿編集画面での Dynamic If ブロックの設定項目は、プルダウン項目で、 is_front_page() と is_single() が選択できます。
* 配置した Dynamic If ブロックに対して、is_front_page() が指定されていたら、そのブロックのインナーブロックに設置されたコンテンツはトップページでのみ表示します。
* 逆に、if_front_page() が指定されている場合、トップページ以外では インナーブロックに配置したコンテンツは表示されないようにしてください。
* 非表示にする条件分岐は php の関数の if_front_page() と is_single() を使ってください。もし JavaScript で同様の処理をできるなら JapaScript で処理しても構いません。

### その他

* プラグインとしてすぐ動くように、ディレクトリ直下にプラグイン情報を記載したPHPファイルも生成してください。
* Dynamic If ブロックは編集画面では赤色の枠線が表示されるようにCSSで指定してください。
* CSSは直接書き込むのではなく、別で src/editor.scss ファイルを用意してそこに記載し、ビルド先は build/editor.css にしてください。
* build/editor.css は編集画面でだけ読み込み、公開画面では読み込まないようにしてください。
* wp-script を使ってビルドする
* npx @wordpress/create-block で作られるようなファイル構成、package.json に沿ったものにしたい
* ブロックに関するファイルは src ディレクトリに配置し、ビルドすると build ディレクトリに出力されます
* src/block.json ファイルも必要
* ブロックに関する動的表示処理をする php は src/index.php に記載する
* .gitignore ファイルは不要です。

## package.json についての補足

package.json も用意してください。

* 開発でのみ必要なパッケージは devDependencies で指定してください。
* package.json には以下も含めてください。
* @wordpress/scripts のバージョンは ^26.1.0 で用意する

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
