ABSの標準音声をGoogle TTS APIのものに変更しました。

標準ではWavenet-Bを音声に使用していますが、変更したい場合にはtemplate.jsonを修正し、csv2txt.shを実行すると各jsonファイルが生成されます。

音声生成するにはgcloudパッケージとsoxが必要です。Google TTS APIの設定を行い、認証情報をgoogle_auth.shに設定する必要があります。

CSVファイル(abs-sounds-src.csv)は"番号,ファイル名,本文"のファイルで、UTF-8で保存してください。csv2txtを実行するとtemplate.jsonをもとに各音声用のjsonを生成します。このとき、ファイル名にはサブディレクトリを含むのでdigits、lettersのディレクトリがあることを確認しておいてください。

拡張子.lin16はPCMリニア16bitの音声ファイルでGoogle TTSから返されるフォーマットのものです(sox変換前)。

音声ファイルを生成するには各ディレクトリ(ja,ja/digits,ja/letters)でmakeを実行してください。生成されるファイルはslin 8kHzの.wavとなります。音声ファイルをAsteriskのディレクトリにコピーするには手動またはmake installを実行してください。この際、/var/lib/asterisk/sounds/ja、ja/digits、ja/lettersディレクトリが作成されていることを確認してください。
